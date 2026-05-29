<?php

namespace App\Controller\Agent;

use App\Repository\AvailabilitySlotRepository;
use App\Repository\PropertyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/agent', name: 'agent_dashboard', methods: ['GET'])]
    /** Fonction qui affiche le tableau de bord agent avec les stats. */
    public function index(
        PropertyRepository $propertyRepository,
        AvailabilitySlotRepository $availabilitySlotRepository
    ): Response
    {
        $totalProperties = $propertyRepository->count([]);
        $publishedProperties = $propertyRepository->count(['status' => 'published']);
        $draftProperties = $propertyRepository->count(['draft' => 'yes']);

        $totalSlots = $availabilitySlotRepository->count([]);
        $availableSlots = $availabilitySlotRepository->count(['isAvailable' => true]);

        $latestProperties = $propertyRepository->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('agent/dashboard/index.html.twig', [
            'stats' => [
                'totalProperties' => $totalProperties,
                'publishedProperties' => $publishedProperties,
                'draftProperties' => $draftProperties,
                'totalSlots' => $totalSlots,
                'availableSlots' => $availableSlots,
            ],
            'latestProperties' => $latestProperties,
        ]);
    }
}
