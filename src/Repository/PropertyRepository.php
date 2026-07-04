<?php

namespace App\Repository;

use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PropertyRepository extends ServiceEntityRepository
{
    // Fonction qui initialise l'objet
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Property::class);
    }

    // Fonction qui retourne tous les biens publiés
    public function findPublished(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', Property::STATUS_PUBLISHED)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Fonction qui cherche les biens sous un budget max
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

    // Fonction qui cherche les biens par mot-clé dans le titre
    public function findPublishedByTitleKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('LOWER(p.title) LIKE :keyword')
            ->setParameter('status', Property::STATUS_PUBLISHED)
            ->setParameter('keyword', '%'.strtolower($keyword).'%')
            ->orderBy('p.price', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    // Fonction qui filtre les biens publiés par critères
    public function findPublishedByFilters(?int $budget, ?string $city, ?string $keyword): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', Property::STATUS_PUBLISHED);

        if ($budget !== null) {
            $qb->andWhere('p.price <= :budget')
                ->setParameter('budget', $budget);
        }

        if ($city !== null && $city !== '') {
            $qb->andWhere('LOWER(p.city) LIKE :city')
                ->setParameter('city', '%'.strtolower($city).'%');
        }

        if ($keyword !== null && $keyword !== '') {
            $qb->andWhere('(LOWER(p.title) LIKE :keyword OR LOWER(p.description) LIKE :keyword)')
                ->setParameter('keyword', '%'.strtolower($keyword).'%');
        }

        return $qb->orderBy('p.price', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
