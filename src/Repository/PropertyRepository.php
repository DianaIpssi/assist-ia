<?php

namespace App\Repository;

use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Property>
 */
class PropertyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Property::class);
    }

    /** @return Property[] */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', Property::STATUS_PUBLISHED)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Property[] */
    public function findPublishedByMaxBudget(int $budget): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.price <= :budget')
            ->setParameter('status', Property::STATUS_PUBLISHED)
            ->setParameter('budget', $budget)
            ->orderBy('p.price', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
