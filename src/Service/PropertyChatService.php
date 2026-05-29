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
        if (preg_match('/\d+/', $message, $matches) !== 1) {
            return null;
        }

        return (int) $matches[0];
    }

    public function buildReply(string $message): string
    {
        $budget = $this->extractBudget(strtolower($message));

        if ($budget === null) {
            return 'Quel est ton budget maximum ?';
        }

        $properties = $this->propertyRepository->findPublishedByMaxBudget($budget);

        if ($properties === []) {
            return sprintf('Je n\'ai trouvé aucun bien publié sous %d €.', $budget);
        }

        $lines = array_map(
            static fn (Property $property): string => sprintf(
                '- %s : %d €',
                $property->getTitle(),
                $property->getPrice()
            ),
            $properties
        );

        return "Voici des biens correspondant à ton budget :\n\n".implode("\n", $lines);
    }
}
