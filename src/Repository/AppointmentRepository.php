<?php

namespace App\Repository;

use App\Entity\Appointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AppointmentRepository extends ServiceEntityRepository
{
    // Fonction qui initialise l'objet
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    // Fonction qui retourne les prochains RDV d'un agent
    public function findUpcomingForAgent(int $agentId): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.slot', 's')
            ->andWhere('a.agent = :agentId')
            ->andWhere('s.startAt >= :now')
            ->setParameter('agentId', $agentId)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
