<?php

namespace App\Controller\Api;

use App\Repository\PropertyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ChatController extends AbstractController
{
    #[Route('/api/chat', name: 'api_chat', methods: ['POST'])]
    public function chat(Request $request, PropertyRepository $repository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = strtolower($data['message'] ?? '');

        // Extraction simple budget (regex)
        preg_match('/\d+/', $message, $matches);
        $budget = $matches[0] ?? null;

        if ($budget) {
            $properties = $repository->createQueryBuilder('p')
                ->where('p.price <= :budget')
                ->setParameter('budget', (int)$budget)
                ->getQuery()
                ->getResult();

            if (count($properties) > 0) {
                $response = "Voici des biens correspondant à ton budget :\n\n";

                foreach ($properties as $property) {
                    $response .= "- " . $property->getTitle() . " : " . $property->getPrice() . " €\n";
                }

                return $this->json(['reply' => $response]);
            }

            return $this->json([
                'reply' => "Je n’ai trouvé aucun bien sous {$budget} € 😕"
            ]);
        }

        return $this->json([
            'reply' => "Quel est ton budget maximum ?"
        ]);
    }
}
