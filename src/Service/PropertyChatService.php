<?php

namespace App\Service;

use App\Entity\ChatMessage;
use App\Entity\Property;
use App\Repository\ChatMessageRepository;
use App\Repository\PropertyRepository;

class PropertyChatService
{
    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly OllamaChatService $ollamaChatService,
        private readonly ChatMessageRepository $chatMessageRepository,
    ) {
    }

    /** Fonction qui extrait un budget en euros depuis un message. */
    public function extractBudget(string $message): ?int
    {
        $normalized = strtolower(trim($message));
        $withoutTypology = preg_replace('/\bt\s*[0-9]+\b/u', '', $normalized) ?? $normalized;

        if (preg_match('/(\d[\d\s]*)\s*(?:€|euros?|eur\b)/u', $normalized, $matches) === 1) {
            $amount = (int) preg_replace('/\s+/', '', $matches[1]);
            if ($amount >= 1000) {
                return $amount;
            }
        }

        if (preg_match_all('/\d+/u', $withoutTypology, $matches) !== false) {
            foreach ($matches[0] as $number) {
                $amount = (int) $number;
                if ($amount >= 10000) {
                    return $amount;
                }
            }
        }

        return null;
    }

    /** Fonction qui détecte un type de bien (T2, studio…) dans un message. */
    public function extractSearchKeyword(string $message): ?string
    {
        $normalized = strtolower(trim($message));

        if (preg_match('/\b(t\s*[0-9]+|studio|maison|appart(?:ement)?)\b/u', $normalized, $matches) === 1) {
            return preg_replace('/\s+/', '', $matches[1]) ?? null;
        }

        return null;
    }

    /** Fonction qui génère la réponse du chatbot (Ollama ou fallback). */
    public function buildReply(string $message, ?string $sessionId = null): string
    {
        if ($this->ollamaChatService->isAvailable()) {
            $ollamaReply = $this->buildReplyWithOllama($message, $sessionId);
            if ($ollamaReply !== null) {
                return $ollamaReply;
            }
        }

        return $this->buildRuleBasedReply($message);
    }

    /** Fonction qui répond avec la logique basique budget / mot-clé. */
    public function buildRuleBasedReply(string $message): string
    {
        $budget = $this->extractBudget($message);

        if ($budget !== null) {
            return $this->formatPropertyList(
                $this->propertyRepository->findPublishedByMaxBudget($budget),
                sprintf('Je n\'ai trouvé aucun bien publié sous %d €.', $budget),
                "Voici des biens correspondant à ton budget :\n\n"
            );
        }

        $keyword = $this->extractSearchKeyword($message);
        if ($keyword !== null) {
            return $this->formatPropertyList(
                $this->propertyRepository->findPublishedByTitleKeyword($keyword),
                sprintf('Je n\'ai trouvé aucun bien publié correspondant à « %s ». Tu peux aussi me donner ton budget.', $keyword),
                sprintf("Voici des biens correspondant à « %s » :\n\n", $keyword)
            );
        }

        return 'Quel est ton budget maximum ? (ex : 150000 €)';
    }

    /** Fonction qui construit une réponse via Ollama avec les biens filtrés. */
    private function buildReplyWithOllama(string $message, ?string $sessionId): ?string
    {
        $criteria = $this->resolveSearchCriteria($message);
        $properties = $this->findPropertiesForCriteria($criteria);
        $propertiesContext = $this->formatPropertiesForPrompt($properties);

        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($propertiesContext)],
        ];

        $history = $this->getRecentHistory($sessionId);
        foreach ($history as $historyMessage) {
            $messages[] = [
                'role' => $historyMessage->getRole() ?? 'user',
                'content' => $historyMessage->getContent() ?? '',
            ];
        }

        if ($history === []) {
            $messages[] = ['role' => 'user', 'content' => $message];
        }

        return $this->ollamaChatService->chat($messages);
    }

    /** Fonction qui extrait budget, ville et type via Ollama + regex. */
    private function resolveSearchCriteria(string $message): array
    {
        $criteria = [
            'budget' => $this->extractBudget($message),
            'city' => null,
            'keyword' => $this->extractSearchKeyword($message),
        ];

        $prompt = <<<PROMPT
Analyse ce message d'un client qui cherche un bien immobilier en France.
Réponds UNIQUEMENT avec un JSON valide, sans markdown ni texte autour :
{"budget":null,"city":null,"keyword":null}

Règles :
- budget : nombre entier en euros ou null
- city : nom de ville ou null
- keyword : studio, t2, maison, appartement ou null

Message : {$message}
PROMPT;

        $response = $this->ollamaChatService->chat([
            ['role' => 'user', 'content' => $prompt],
        ]);

        if ($response === null) {
            return $criteria;
        }

        if (preg_match('/\{.*\}/s', $response, $matches) !== 1) {
            return $criteria;
        }

        try {
            /** @var array{budget?: mixed, city?: mixed, keyword?: mixed} $parsed */
            $parsed = json_decode($matches[0], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $criteria;
        }

        if (isset($parsed['budget']) && is_numeric($parsed['budget'])) {
            $criteria['budget'] = (int) $parsed['budget'];
        }

        if (isset($parsed['city']) && \is_string($parsed['city']) && trim($parsed['city']) !== '') {
            $criteria['city'] = trim($parsed['city']);
        }

        if (isset($parsed['keyword']) && \is_string($parsed['keyword']) && trim($parsed['keyword']) !== '') {
            $criteria['keyword'] = trim($parsed['keyword']);
        }

        return $criteria;
    }

    /** Fonction qui cherche les biens publiés selon les critères extraits. */
    private function findPropertiesForCriteria(array $criteria): array
    {
        $hasFilter = $criteria['budget'] !== null
            || ($criteria['city'] !== null && $criteria['city'] !== '')
            || ($criteria['keyword'] !== null && $criteria['keyword'] !== '');

        if (!$hasFilter) {
            return $this->propertyRepository->findPublished();
        }

        return $this->propertyRepository->findPublishedByFilters(
            $criteria['budget'],
            $criteria['city'],
            $criteria['keyword'],
        );
    }

    /** Fonction qui formate la liste des biens pour le prompt Ollama. */
    private function formatPropertiesForPrompt(array $properties): string
    {
        if ($properties === []) {
            return 'Aucun bien ne correspond aux critères pour le moment.';
        }

        $lines = array_map(
            static function (Property $property): string {
                $parts = [
                    $property->getTitle(),
                    $property->getCity() ?: 'ville non renseignée',
                    number_format((int) $property->getPrice(), 0, ',', ' ').' €',
                ];

                if ($property->getSurface() !== null) {
                    $parts[] = $property->getSurface().' m²';
                }

                if ($property->getRooms() !== null) {
                    $parts[] = $property->getRooms().' pièce(s)';
                }

                return '- '.implode(' | ', $parts);
            },
            $properties
        );

        return implode("\n", $lines);
    }

    /** Fonction qui prépare le prompt système envoyé à Ollama. */
    private function buildSystemPrompt(string $propertiesContext): string
    {
        return <<<PROMPT
Tu es l'assistant Assist'IA, une agence immobilière en ligne.
Tu aides les visiteurs à trouver un bien immobilier en France.

Règles importantes :
- Réponds en français, de façon concise et amicale (tutoiement OK).
- Ne invente JAMAIS de biens : utilise uniquement la liste ci-dessous.
- Si aucun bien ne convient, demande budget, ville ou type de bien, ou propose de prendre RDV (/rdv).
- Tu peux inviter à parcourir toutes les annonces sur /biens.

Biens disponibles :
{$propertiesContext}
PROMPT;
    }

    /** Fonction qui récupère les derniers messages d'une session de chat. */
    private function getRecentHistory(?string $sessionId, int $limit = 10): array
    {
        if ($sessionId === null) {
            return [];
        }

        $messages = $this->chatMessageRepository->findBySessionId($sessionId);

        return \array_slice($messages, -$limit);
    }

    /** Fonction qui formate une liste de biens en texte pour le chat. */
    private function formatPropertyList(array $properties, string $emptyMessage, string $intro): string
    {
        if ($properties === []) {
            return $emptyMessage;
        }

        $lines = array_map(
            static fn (Property $property): string => sprintf(
                '- %s : %d €',
                $property->getTitle(),
                $property->getPrice()
            ),
            $properties
        );

        return $intro.implode("\n", $lines);
    }
}
