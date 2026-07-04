<?php

namespace App\Service;

use App\Entity\Property;
use App\Repository\PropertyRepository;

class PropertyChatService
{
    // Fonction qui initialise l'objet
    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly OllamaChatService $ollamaChatService,
    ) {
    }

    // Fonction qui extrait le budget depuis un message
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

    // Fonction qui extrait le type de bien recherché
    public function extractSearchKeyword(string $message): ?string
    {
        $normalized = strtolower(trim($message));

        if (preg_match('/\b(t\s*[0-9]+|studio|maison|appart(?:ement)?)\b/u', $normalized, $matches) === 1) {
            return preg_replace('/\s+/', '', $matches[1]) ?? null;
        }

        return null;
    }

    // Fonction qui construit la réponse du chat
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

    // Fonction qui répond avec des règles simples sans IA
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

    // Fonction qui répond en utilisant Ollama
    private function buildReplyWithOllama(string $message, ?string $sessionId): ?string
    {
        $criteria = $this->resolveSearchCriteria($message);
        $properties = $this->findPropertiesForCriteria($criteria);

        if ($properties === []) {
            $allPublished = $this->propertyRepository->findPublished();
            if ($allPublished === []) {
                return 'Aucun bien publié pour le moment. Consulte /biens plus tard ou prends RDV sur /rdv.';
            }

            return $this->formatPropertyList(
                $allPublished,
                '',
                "Je n'ai pas trouvé de bien correspondant exactement à ta recherche. Voici nos annonces publiées actuellement :\n\n"
            );
        }

        $intro = $this->buildSearchIntro($criteria, \count($properties));

        return $this->formatPropertyList($properties, '', $intro);
    }

    // Fonction qui prépare le texte d'intro des résultats
    private function buildSearchIntro(array $criteria, int $count): string
    {
        $parts = [];
        if ($criteria['keyword'] !== null && $criteria['keyword'] !== '') {
            $parts[] = 'type « '.$criteria['keyword'].' »';
        }
        if ($criteria['city'] !== null && $criteria['city'] !== '') {
            $parts[] = 'à '.$criteria['city'];
        }
        if ($criteria['budget'] !== null) {
            $parts[] = 'budget max '.number_format($criteria['budget'], 0, ',', ' ').' €';
        }

        if ($parts === []) {
            return sprintf("Voici %d bien(s) disponible(s) sur notre site :\n\n", $count);
        }

        return sprintf("Voici %d bien(s) correspondant à ta recherche (%s) — uniquement nos annonces réelles :\n\n", $count, implode(', ', $parts));
    }

    // Fonction qui analyse le message pour trouver les critères
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

    // Fonction qui cherche les biens selon les critères
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

    // Fonction qui formate la liste des biens en texte
    private function formatPropertyList(array $properties, string $emptyMessage, string $intro): string
    {
        if ($properties === []) {
            return $emptyMessage !== '' ? $emptyMessage : 'Aucun bien trouvé. Consulte /biens pour voir le catalogue.';
        }

        $lines = array_map(
            static fn (Property $property): string => sprintf(
                "• %s – %s – %s €\n  → /biens/%d",
                $property->getTitle(),
                $property->getCity() ?: 'ville non renseignée',
                number_format((int) $property->getPrice(), 0, ',', ' '),
                (int) $property->getId()
            ),
            $properties
        );

        $outro = "\n\nToutes les annonces : /biens";

        return $intro.implode("\n\n", $lines).$outro;
    }
}
