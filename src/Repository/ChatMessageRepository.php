<?php

namespace App\Repository;

use App\Entity\ChatMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ChatMessageRepository extends ServiceEntityRepository
{
    // Fonction qui initialise l'objet
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatMessage::class);
    }

    // Fonction qui liste les conversations chat enregistrées
    public function findSessionSummaries(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.sessionId AS sessionId')
            ->addSelect('MAX(c.clientName) AS clientName')
            ->addSelect('MAX(c.clientEmail) AS clientEmail')
            ->addSelect('MAX(c.createdAt) AS lastAt')
            ->addSelect('COUNT(c.id) AS messageCount')
            ->groupBy('c.sessionId')
            ->orderBy('lastAt', 'DESC')
            ->getQuery()
            ->getArrayResult();

        $summaries = [];
        foreach ($rows as $row) {
            $lastMessage = $this->createQueryBuilder('m')
                ->andWhere('m.sessionId = :sessionId')
                ->setParameter('sessionId', $row['sessionId'])
                ->orderBy('m.createdAt', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            $summaries[] = [
                'sessionId' => $row['sessionId'],
                'clientName' => $row['clientName'],
                'clientEmail' => $row['clientEmail'],
                'lastMessage' => $lastMessage?->getContent() ?? '',
                'lastAt' => $lastMessage?->getCreatedAt() ?? new \DateTimeImmutable(),
                'messageCount' => (int) $row['messageCount'],
            ];
        }

        return $summaries;
    }

    // Fonction qui retourne les messages d'une conversation
    public function findBySessionId(string $sessionId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.sessionId = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Fonction qui supprime les messages plus anciens que la date limite (RGPD)
    public function deleteOlderThan(\DateTimeImmutable $limit): int
    {
        return $this->createQueryBuilder('c')
            ->delete()
            ->andWhere('c.createdAt < :limit')
            ->setParameter('limit', $limit)
            ->getQuery()
            ->execute();
    }
}
