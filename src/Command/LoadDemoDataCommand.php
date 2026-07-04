<?php

namespace App\Command;

use App\Entity\Agent;
use App\Entity\Property;
use App\Repository\PropertyRepository;
use App\Service\AvailabilitySlotGeneratorService;
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
    /** @var list<array{title: string, price: int, status: string, city: string, postalCode: string, description: string, imageUrl: string, surface: int, rooms: int, bedrooms: int}> */
    private const DEMO_PROPERTIES = [
        [
            'title' => 'Studio lumineux',
            'price' => 95000,
            'status' => Property::STATUS_PUBLISHED,
            'city' => 'Paris',
            'postalCode' => '75011',
            'description' => 'Studio refait à neuf, proche métro et commerces.',
            'imageUrl' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800&q=80',
            'surface' => 28,
            'rooms' => 1,
            'bedrooms' => 1,
        ],
        [
            'title' => 'T2 avec balcon',
            'price' => 180000,
            'status' => Property::STATUS_PUBLISHED,
            'city' => 'Lyon',
            'postalCode' => '69003',
            'description' => 'Appartement T2 lumineux avec balcon, proche centre-ville.',
            'imageUrl' => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&q=80',
            'surface' => 52,
            'rooms' => 2,
            'bedrooms' => 1,
        ],
        [
            'title' => 'Maison familiale',
            'price' => 320000,
            'status' => Property::STATUS_DRAFT,
            'city' => 'Bordeaux',
            'postalCode' => '33000',
            'description' => 'Maison avec jardin, idéale pour une famille.',
            'imageUrl' => 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=800&q=80',
            'surface' => 120,
            'rooms' => 5,
            'bedrooms' => 3,
        ],
    ];

    // Fonction qui initialise l'objet
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly AvailabilitySlotGeneratorService $slotGenerator,
    ) {
        parent::__construct();
    }

    // Fonction qui exécute la commande console
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

        /** @var PropertyRepository $propertyRepository */
        $propertyRepository = $this->entityManager->getRepository(Property::class);

        foreach (self::DEMO_PROPERTIES as $data) {
            $property = $propertyRepository->findOneBy(['title' => $data['title']]);

            if (!$property) {
                $property = new Property();
                $property->setAgent($agent);
                $this->entityManager->persist($property);
            }

            $property
                ->setTitle($data['title'])
                ->setPrice($data['price'])
                ->setDescription($data['description'])
                ->setImageUrl($data['imageUrl'])
                ->setStatus($data['status'])
                ->setCity($data['city'])
                ->setPostalCode($data['postalCode'])
                ->setSurface($data['surface'])
                ->setRooms($data['rooms'])
                ->setBedrooms($data['bedrooms']);
        }

        $this->entityManager->flush();
        $this->slotGenerator->ensureUpcomingSlots();

        $io->success('Données de démonstration chargées.');
        $io->listing([
            'Agent : agent@assist-ia.fr / Agent123!',
            'Admin : admin@assist-ia.fr / Admin123!',
        ]);

        return Command::SUCCESS;
    }
}
