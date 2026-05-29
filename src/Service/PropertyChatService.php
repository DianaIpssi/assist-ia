<?php

namespace App\Service;

use App\Entity\Property;
use App\Repository\PropertyRepository;

class PropertyChatService
{
    public function __construct(private readonly PropertyRepository $propertyRepository)
    {
    }

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

    public function extractSearchKeyword(string $message): ?string
    {
        $normalized = strtolower(trim($message));

        if (preg_match('/\b(t\s*[0-9]+|studio)\b/u', $normalized, $matches) === 1) {
            return preg_replace('/\s+/', '', $matches[1]) ?? null;
        }

        return null;
    }

    public function buildReply(string $message): string
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

    /** @param Property[] $properties */
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
