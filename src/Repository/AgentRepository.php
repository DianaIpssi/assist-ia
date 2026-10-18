<?php

namespace App\Repository;

use App\Entity\Agent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class AgentRepository extends ServiceEntityRepository
{
    // Fonction qui initialise l'objet
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Agent::class);
    }

    /** @return list<Agent> */
    // Fonction qui retourne les agents réservables par un visiteur (hors administrateurs)
    public function findBookableAgents(): array
    {
        $agents = $this->findBy([], ['fullName' => 'ASC']);

        return array_values(array_filter(
            $agents,
            static fn (Agent $agent): bool => \in_array('ROLE_AGENT', $agent->getRoles(), true)
                && !\in_array('ROLE_ADMIN', $agent->getRoles(), true)
        ));
    }
}
