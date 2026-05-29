<?php

namespace App\Controller\Agent;

use App\Repository\ChatMessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ChatHistoryController extends AbstractController
{
    #[Route('/agent/chats', name: 'agent_chats')]
    /** Fonction qui liste les conversations clients pour l'agent. */
    public function index(ChatMessageRepository $chatMessageRepository): Response
    {
        return $this->render('agent/chat/index.html.twig', [
            'sessions' => $chatMessageRepository->findSessionSummaries(),
        ]);
    }

    #[Route('/agent/chats/{sessionId}', name: 'agent_chat_show')]
    /** Fonction qui affiche une conversation chat en détail. */
    public function show(string $sessionId, ChatMessageRepository $chatMessageRepository): Response
    {
        $messages = $chatMessageRepository->findBySessionId($sessionId);
        if ($messages === []) {
            throw $this->createNotFoundException('Conversation introuvable.');
        }

        return $this->render('agent/chat/show.html.twig', [
            'sessionId' => $sessionId,
            'messages' => $messages,
        ]);
    }
}
