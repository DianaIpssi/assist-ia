<?php

namespace App\Tests\Functional\Command;

use App\Entity\ChatMessage;
use App\Tests\DatabaseWebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class PurgeChatMessagesCommandTest extends DatabaseWebTestCase
{
    // Fonction qui vérifie que seuls les messages de plus de 12 mois sont supprimés
    public function testPurgeDeletesOnlyOldMessages(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $old = (new ChatMessage())
            ->setSessionId('ancienne')
            ->setRole('user')
            ->setContent('je cherche un T2')
            ->setCreatedAt(new \DateTimeImmutable('-13 months'));
        $recent = (new ChatMessage())
            ->setSessionId('recente')
            ->setRole('user')
            ->setContent('un studio à Paris');
        $entityManager->persist($old);
        $entityManager->persist($recent);
        $entityManager->flush();

        $tester = new CommandTester((new Application($client->getKernel()))->find('app:purge-chat-messages'));
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        $remaining = $entityManager->getRepository(ChatMessage::class)->findAll();
        $this->assertCount(1, $remaining);
        $this->assertSame('recente', $remaining[0]->getSessionId());
    }
}
