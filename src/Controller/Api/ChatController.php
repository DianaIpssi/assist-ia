<?php

namespace App\Controller\Api;

use App\Service\PropertyChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ChatController extends AbstractController
{
    #[Route('/api/chat', name: 'api_chat', methods: ['POST'])]
    public function chat(Request $request, PropertyChatService $propertyChatService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = is_array($data) ? (string) ($data['message'] ?? '') : '';

        if (trim($message) === '') {
            return $this->json([
                'reply' => 'Merci de préciser votre budget ou votre recherche.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'reply' => $propertyChatService->buildReply($message),
        ]);
    }
}
