<?php

namespace App\Controller\Agent;

use App\Entity\Agent;
use App\Entity\AvailabilitySlot;
use App\Repository\AppointmentRepository;
use App\Repository\AvailabilitySlotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CalendarController extends AbstractController
{
    #[Route('/agent/calendrier', name: 'agent_calendar')]
    // Fonction qui affiche le calendrier et les RDV de l'agent. 
    public function index(
        AvailabilitySlotRepository $slotRepository,
        AppointmentRepository $appointmentRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof Agent) {
            throw $this->createAccessDeniedException();
        }

        $slots = $slotRepository->createQueryBuilder('s')
            ->andWhere('s.agent = :agent OR s.agent IS NULL')
            ->setParameter('agent', $user)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('agent/calendar/index.html.twig', [
            'slots' => $slots,
            'appointments' => $appointmentRepository->findUpcomingForAgent((int) $user->getId()),
        ]);
    }

    #[Route('/agent/calendrier/nouveau', name: 'agent_calendar_new', methods: ['GET', 'POST'])]
    // Fonction qui ajoute un nouveau créneau de disponibilité. 
    public function newSlot(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Agent) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $date = $request->request->getString('date');
            $startTime = $request->request->getString('start_time');
            $endTime = $request->request->getString('end_time');

            if ($date && $startTime && $endTime) {
                $slot = (new AvailabilitySlot())
                    ->setAgent($user)
                    ->setStartAt(new \DateTimeImmutable($date.' '.$startTime))
                    ->setEndAt(new \DateTimeImmutable($date.' '.$endTime))
                    ->setIsAvailable(true)
                    ->setNote('Créneau agent');

                $entityManager->persist($slot);
                $entityManager->flush();

                $this->addFlash('success', 'Créneau ajouté au calendrier.');

                return $this->redirectToRoute('agent_calendar');
            }

            $this->addFlash('error', 'Merci de remplir tous les champs.');
        }

        return $this->render('agent/calendar/new.html.twig');
    }
}
