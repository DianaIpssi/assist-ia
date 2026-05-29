<?php

namespace App\Controller\Api;

use App\Service\AddressApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class AddressController extends AbstractController
{
    #[Route('/api/address/search', name: 'api_address_search', methods: ['GET'])]
    /** Fonction qui recherche une adresse via l'API gouvernementale. */
    public function search(Request $request, AddressApiService $addressApiService): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        if ($query === '') {
            return $this->json([
                'results' => [],
                'message' => 'Le parametre "q" est requis.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'results' => $addressApiService->search($query),
        ]);
    }
}
