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
        $intentReply = $this->buildIntentReply($message);
        if ($intentReply !== null) {
            return $intentReply;
        }

        if ($this->ollamaChatService->isAvailable()) {
            $ollamaReply = $this->buildReplyWithOllama($message, $sessionId);
            if ($ollamaReply !== null) {
                return $ollamaReply;
            }
        }

        return $this->buildRuleBasedReply($message);
    }

    // Fonction qui répond aux salutations et aux demandes de RDV ou de contact
    public function buildIntentReply(string $message): ?string
    {
        $normalized = mb_strtolower(trim($message));

        if (preg_match('/\b(rdv|rendez[\s-]?vous|cr[ée]neaux?|r[ée]serv\w*)\b/u', $normalized) === 1) {
            return "Je ne peux pas réserver à ta place, mais c'est rapide : choisis ton agent puis un créneau sur /rdv.\n\n"
                ."Depuis la fiche d'un bien, le bouton « Prendre RDV avec cet agent » t'amène directement à ses créneaux.";
        }

        if (preg_match('/\b(contact\w*|joindre|t[ée]l[ée]phone|e-?mail)\b/u', $normalized) === 1) {
            return "Tu peux écrire à l'agence sur /contact.\n\n"
                ."Pour une question sur un bien précis, utilise le bouton « Contacter l'agent » sur sa fiche : ton message ira directement à son agent.";
        }

        $withoutGreeting = preg_replace('/\b(bonjour|bonsoir|salut|hello|coucou|hey|ça va|ca va)\b|[\s!?.,]/u', '', $normalized) ?? $normalized;
        if ($withoutGreeting === '' && $normalized !== '') {
            return "Bonjour ! Je suis l'assistant Assist'IA. Je t'aide à trouver un bien parmi nos annonces.\n\n"
                .'Quel est ton budget maximum ? (ex : 150000 €) Tu peux aussi me donner un type de bien (studio, T2, maison) ou une ville.';
        }

        return null;
    }

    // Fonction qui répond avec des règles simples sans IA
    public function buildRuleBasedReply(string $message): string
    {
        $budget = $this->extractBudget($message);

        if ($budget !== null) {
            return $this->formatPropertyList(
                $this->propertyRepository->findPublishedByMaxBudget($budget),
                sprintf('Je n\'ai trouvé aucun bien publié sous %d €.', $budget),
                "Voici des biens correspondant à ton budget :\n\n",
                ['budget' => $budget]
            );
        }

        $keyword = $this->extractSearchKeyword($message);
        if ($keyword !== null) {
            return $this->formatPropertyList(
                $this->propertyRepository->findPublishedByTitleKeyword($keyword),
                sprintf('Je n\'ai trouvé aucun bien publié correspondant à « %s ». Tu peux aussi me donner ton budget.', $keyword),
                sprintf("Voici des biens correspondant à « %s » :\n\n", $keyword),
                ['keyword' => $keyword]
            );
        }

        return 'Quel est ton budget maximum ? (ex : 150000 €)';
    }

    // Fonction qui répond en utilisant Ollama
    private function buildReplyWithOllama(string $message, ?string $sessionId): ?string
    {
        $criteria = $this->resolveSearchCriteria($message);
        if ($criteria['budget'] === null && ($criteria['city'] ?? '') === '' && ($criteria['keyword'] ?? '') === '') {
            return $this->buildRuleBasedReply($message);
        }

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

        return $this->formatPropertyList($properties, '', $intro, $criteria);
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

        $prompt = <<<'PROMPT'
Tu analyses le message d'un client qui cherche un bien immobilier en France.
Réponds UNIQUEMENT avec ce JSON : {"budget":null,"city":null,"keyword":null}

Règles :
- budget : nombre entier en euros ou null ("200k" = 200000)
- city : nom de ville écrit dans le message, ou null
- keyword : studio, t2, t3, maison ou appartement, seulement s'il est écrit dans le message, sinon null
- n'invente jamais une valeur absente du message

Exemples :
Message : Je cherche un T2 à Lyon pour 200000 €
{"budget":200000,"city":"Lyon","keyword":"t2"}
Message : un studio à Paris
{"budget":null,"city":"Paris","keyword":"studio"}
Message : une maison, budget max 350k
{"budget":350000,"city":null,"keyword":"maison"}
Message : bonjour, je débute ma recherche
{"budget":null,"city":null,"keyword":null}
PROMPT;

        $response = $this->ollamaChatService->chat([
            ['role' => 'system', 'content' => $prompt],
            ['role' => 'user', 'content' => $message],
        ], true);

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

        if (isset($parsed['budget']) && is_numeric($parsed['budget']) && $this->hasNumber($message)) {
            $criteria['budget'] = (int) $parsed['budget'];
        }

        if (isset($parsed['city']) && \is_string($parsed['city']) && $this->isInMessage($parsed['city'], $message)) {
            $criteria['city'] = trim($parsed['city']);
        }

        if (isset($parsed['keyword']) && \is_string($parsed['keyword']) && $this->isInMessage($parsed['keyword'], $message)) {
            $criteria['keyword'] = trim($parsed['keyword']);
        }

        return $criteria;
    }

    // Fonction qui vérifie qu'un critère proposé par l'IA figure bien dans le message
    private function isInMessage(string $value, string $message): bool
    {
        $value = mb_strtolower(trim($value));

        return $value !== '' && str_contains(mb_strtolower($message), $value);
    }

    // Fonction qui vérifie que le message contient un montant (hors T2, T3...), sinon le budget proposé par l'IA est inventé
    private function hasNumber(string $message): bool
    {
        $withoutTypology = preg_replace('/\bt\s*[0-9]+\b/u', '', mb_strtolower($message)) ?? $message;

        return preg_match('/\d/', $withoutTypology) === 1;
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

    // Fonction qui construit le lien vers le catalogue filtré avec les critères compris
    public function buildCatalogueLink(array $criteria): string
    {
        $query = array_filter([
            'ville' => $criteria['city'] ?? null,
            'type' => isset($criteria['keyword']) ? strtolower($criteria['keyword']) : null,
            'budget' => $criteria['budget'] ?? null,
        ], static fn ($value): bool => $value !== null && $value !== '');

        return $query === [] ? '/biens' : '/biens?'.http_build_query($query);
    }

    // Fonction qui formate la liste des biens en texte
    private function formatPropertyList(array $properties, string $emptyMessage, string $intro, array $criteria = []): string
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

        $catalogueLink = $this->buildCatalogueLink($criteria);
        $outro = $catalogueLink === '/biens'
            ? "\n\nToutes les annonces : /biens"
            : "\n\nVoir ces résultats dans le catalogue : ".$catalogueLink;

        return $intro.implode("\n\n", $lines).$outro;
    }
}
