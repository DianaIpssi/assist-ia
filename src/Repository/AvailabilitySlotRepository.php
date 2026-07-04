<?php

namespace App\Repository;

use App\Entity\Agent;
use App\Entity\AvailabilitySlot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AvailabilitySlotRepository extends ServiceEntityRepository
{
    // Fonction qui initialise l'objet
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvailabilitySlot::class);
    }

    // Fonction qui retourne les créneaux libres à venir
    public function findAvailableUpcoming(int $limit = 20): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.isAvailable = :available')
            ->andWhere('s.startAt >= :now')
            ->setParameter('available', true)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('s.startAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    // Fonction qui vérifie si un créneau existe déjà à cette date
    public function existsForAgentAt(Agent $agent, \DateTimeImmutable $startAt): bool
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.agent = :agent')
            ->andWhere('s.startAt = :startAt')
            ->setParameter('agent', $agent)
            ->setParameter('startAt', $startAt)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
