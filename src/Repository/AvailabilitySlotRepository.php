<?php

namespace App\Repository;

use App\Entity\AvailabilitySlot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AvailabilitySlot>
 */
class AvailabilitySlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvailabilitySlot::class);
    }

    /** Fonction qui retourne les créneaux disponibles à venir. */
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
}
