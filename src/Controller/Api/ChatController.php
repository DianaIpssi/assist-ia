<?php

namespace App\Controller\Api;

use App\Service\ChatStorageService;
use App\Service\PropertyChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ChatController extends AbstractController
{
    #[Route('/api/chat', name: 'api_chat', methods: ['POST'])]
    // Fonction qui traite un message chat et renvoie la réponse JSON. 
    public function chat(
        Request $request,
        PropertyChatService $propertyChatService,
        ChatStorageService $chatStorageService,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $message = is_array($data) ? trim((string) ($data['message'] ?? '')) : '';
        $sessionId = is_array($data) ? trim((string) ($data['sessionId'] ?? '')) : '';

        if ($message === '') {
            return $this->json([
                'reply' => 'Merci de préciser votre budget ou votre recherche.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($sessionId === '') {
            $sessionId = bin2hex(random_bytes(16));
        }

        $clientName = is_array($data) ? ($data['clientName'] ?? null) : null;
        $clientEmail = is_array($data) ? ($data['clientEmail'] ?? null) : null;

        $chatStorageService->save($sessionId, 'user', $message, $clientName, $clientEmail);
        $reply = $propertyChatService->buildReply($message, $sessionId);
        $chatStorageService->save($sessionId, 'assistant', $reply, $clientName, $clientEmail);

        return $this->json([
            'reply' => $reply,
            'sessionId' => $sessionId,
        ]);
    }
}
