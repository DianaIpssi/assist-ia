<?php

namespace App\Command;

use App\Entity\Agent;
use App\Entity\Property;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:load-demo-data',
    description: 'Charge les comptes de démonstration et des biens exemples',
)]
class LoadDemoDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $agent = $this->entityManager->getRepository(Agent::class)->findOneBy(['email' => 'agent@assist-ia.fr']);
        if (!$agent) {
            $agent = (new Agent())
                ->setEmail('agent@assist-ia.fr')
                ->setFullName('Agent Démo')
                ->setRoles(['ROLE_AGENT']);
            $agent->setPassword($this->passwordHasher->hashPassword($agent, 'Agent123!'));
            $this->entityManager->persist($agent);
        }

        $admin = $this->entityManager->getRepository(Agent::class)->findOneBy(['email' => 'admin@assist-ia.fr']);
        if (!$admin) {
            $admin = (new Agent())
                ->setEmail('admin@assist-ia.fr')
                ->setFullName('Administrateur')
                ->setRoles(['ROLE_ADMIN', 'ROLE_AGENT']);
            $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin123!'));
            $this->entityManager->persist($admin);
        }

        if ($this->entityManager->getRepository(Property::class)->count([]) === 0) {
            $properties = [
                ['Studio lumineux', 95000, Property::STATUS_PUBLISHED, 'Paris', '75011'],
                ['T2 avec balcon', 180000, Property::STATUS_PUBLISHED, 'Lyon', '69003'],
                ['Maison familiale', 320000, Property::STATUS_DRAFT, 'Bordeaux', '33000'],
            ];

            foreach ($properties as [$title, $price, $status, $city, $postalCode]) {
                $property = (new Property())
                    ->setTitle($title)
                    ->setPrice($price)
                    ->setDescription('Bien de démonstration Assist\'IA.')
                    ->setStatus($status)
                    ->setCity($city)
                    ->setPostalCode($postalCode)
                    ->setSurface(45)
                    ->setRooms(2)
                    ->setBedrooms(1)
                    ->setAgent($agent);

                $this->entityManager->persist($property);
            }
        }

        $this->entityManager->flush();

        $io->success('Données de démonstration chargées.');
        $io->listing([
            'Agent : agent@assist-ia.fr / Agent123!',
            'Admin : admin@assist-ia.fr / Admin123!',
        ]);

        return Command::SUCCESS;
    }
}
