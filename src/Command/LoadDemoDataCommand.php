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
    /** @var array<string, array{fullName: string, agencyName: string}> */
    private const DEMO_AGENTS = [
        'agent@assist-ia.fr' => ['fullName' => 'Camille Durand', 'agencyName' => 'Agence Lumière Immobilier'],
        'thomas.lefevre@assist-ia.fr' => ['fullName' => 'Thomas Lefèvre', 'agencyName' => 'Rhône Habitat'],
        'julie.moreau@assist-ia.fr' => ['fullName' => 'Julie Moreau', 'agencyName' => 'Océane Immobilier'],
        'karim.benali@assist-ia.fr' => ['fullName' => 'Karim Benali', 'agencyName' => 'Azur Immobilier'],
        'lea.rousseau@assist-ia.fr' => ['fullName' => 'Léa Rousseau', 'agencyName' => 'Nord Habitat'],
    ];

    /** @var list<array{title: string, price: int, status: string, city: string, postalCode: string, description: string, imageUrl: string, surface: int, rooms: int, bedrooms: int, agent: string}> */
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
            'agent' => 'agent@assist-ia.fr',
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
            'agent' => 'agent@assist-ia.fr',
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
            'agent' => 'agent@assist-ia.fr',
        ],
        [
            'title' => 'Appartement T3 Croix-Rousse',
            'price' => 265000,
            'status' => Property::STATUS_PUBLISHED,
            'city' => 'Lyon',
            'postalCode' => '69004',
            'description' => 'T3 traversant avec parquet ancien, à deux pas des pentes de la Croix-Rousse.',
            'imageUrl' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&q=80',
            'surface' => 68,
            'rooms' => 3,
            'bedrooms' => 2,
            'agent' => 'thomas.lefevre@assist-ia.fr',
        ],
        [
            'title' => 'Loft industriel Confluence',
            'price' => 345000,
            'status' => Property::STATUS_PUBLISHED,
            'city' => 'Lyon',
            'postalCode' => '69002',
            'description' => 'Loft lumineux dans une ancienne halle rénovée, quartier Confluence.',
            'imageUrl' => 'https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=800&q=80',
            'surface' => 95,
            'rooms' => 3,
            'bedrooms' => 2,
            'agent' => 'thomas.lefevre@assist-ia.fr',
        ],
        [
            'title' => 'Échoppe bordelaise rénovée',
            'price' => 410000,
            'status' => Property::STATUS_PUBLISHED,
            'city' => 'Bordeaux',
            'postalCode' => '33800',
            'description' => 'Échoppe en pierre avec patio, entièrement rénovée.',
            'imageUrl' => 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?w=800&q=80',
            'surface' => 110,
            'rooms' => 4,
            'bedrooms' => 3,
            'agent' => 'julie.moreau@assist-ia.fr',
        ],
        [
            'title' => 'T2 proche Île de Nantes',
            'price' => 189000,
            'status' => Property::STATUS_PUBLISHED,
            'city' => 'Nantes',
            'postalCode' => '44200',
            'description' => 'T2 moderne avec cuisine équipée, proche tramway.',
            'imageUrl' => 'https://images.unsplash.com/photo-1484154218962-a197022b5858?w=800&q=80',
            'surface' => 45,
            'rooms' => 2,
            'bedrooms' => 1,
            'agent' => 'julie.moreau@assist-ia.fr',
        ],
        [
            'title' => 'Villa avec piscine',
            'price' => 690000,
            'status' => Property::STATUS_PUBLISHED,
            'city' => 'Nice',
            'postalCode' => '06200',
            'description' => 'Villa sur les hauteurs avec piscine et vue dégagée.',
            'imageUrl' => 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?w=800&q=80',
            'surface' => 160,
            'rooms' => 6,
            'bedrooms' => 4,
            'agent' => 'karim.benali@assist-ia.fr',
        ],
        [
            'title' => 'T3 vue mer',
            'price' => 375000,
            'status' => Property::STATUS_PUBLISHED,
            'city' => 'Marseille',
            'postalCode' => '13007',
            'description' => 'T3 avec terrasse et vue mer, quartier Endoume.',
            'imageUrl' => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=800&q=80',
            'surface' => 72,
            'rooms' => 3,
            'bedrooms' => 2,
            'agent' => 'karim.benali@assist-ia.fr',
        ],
        [
            'title' => 'Maison de ville avec jardin',
            'price' => 299000,
            'status' => Property::STATUS_PUBLISHED,
            'city' => 'Lille',
            'postalCode' => '59000',
            'description' => 'Maison de ville en briques avec jardin, proche Vieux-Lille.',
            'imageUrl' => 'https://images.unsplash.com/photo-1570129477492-45c003edd2be?w=800&q=80',
            'surface' => 105,
            'rooms' => 5,
            'bedrooms' => 3,
            'agent' => 'lea.rousseau@assist-ia.fr',
        ],
        [
            'title' => 'Studio étudiant',
            'price' => 118000,
            'status' => Property::STATUS_PUBLISHED,
            'city' => 'Lille',
            'postalCode' => '59800',
            'description' => 'Studio meublé, idéal étudiant ou investissement locatif.',
            'imageUrl' => 'https://images.unsplash.com/photo-1505691938895-1758d7feb511?w=800&q=80',
            'surface' => 22,
            'rooms' => 1,
            'bedrooms' => 1,
            'agent' => 'lea.rousseau@assist-ia.fr',
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

        $agents = [];
        foreach (self::DEMO_AGENTS as $email => $data) {
            $agent = $this->entityManager->getRepository(Agent::class)->findOneBy(['email' => $email]);
            if (!$agent) {
                $agent = (new Agent())
                    ->setEmail($email)
                    ->setFullName($data['fullName'])
                    ->setAgencyName($data['agencyName'])
                    ->setRoles(['ROLE_AGENT']);
                $agent->setPassword($this->passwordHasher->hashPassword($agent, 'Agent123!'));
                $this->entityManager->persist($agent);
            }

            $agents[$email] = $agent;
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
                $property->setAgent($agents[$data['agent']]);
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
