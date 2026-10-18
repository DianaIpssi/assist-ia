<?php

namespace App\Controller\Public;

use App\Entity\Appointment;
use App\Form\AppointmentType;
use App\Service\AppointmentMailerService;
use App\Service\AvailabilitySlotGeneratorService;
use App\Repository\AgentRepository;
use App\Repository\AvailabilitySlotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AppointmentController extends AbstractController
{
    #[Route('/rdv', name: 'public_appointment')]
    // Fonction qui permet au client de réserver un créneau RDV. 
    public function book(
        Request $request,
        AvailabilitySlotRepository $slotRepository,
        EntityManagerInterface $entityManager,
        AppointmentMailerService $appointmentMailerService,
        AvailabilitySlotGeneratorService $slotGenerator,
        AgentRepository $agentRepository,
    ): Response {
        $slotGenerator->ensureUpcomingSlots();

        $agents = $agentRepository->findBookableAgents();
        $agentId = $request->query->getInt('agent');
        $selectedAgent = null;
        foreach ($agents as $agent) {
            if ($agent->getId() === $agentId) {
                $selectedAgent = $agent;
            }
        }

        if (!$selectedAgent) {
            return $this->render('public/appointment/agents.html.twig', [
                'agents' => $agents,
            ]);
        }

        $availableSlots = $slotRepository->findAvailableUpcomingForAgent($selectedAgent);

        $appointment = new Appointment();
        $form = $this->createForm(AppointmentType::class, $appointment, [
            'available_slots' => $availableSlots,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slot = $appointment->getSlot();
            if (!$slot || !$slot->isAvailable()) {
                $this->addFlash('error', 'Ce créneau n\'est plus disponible.');

                return $this->redirectToRoute('public_appointment', ['agent' => $selectedAgent->getId()]);
            }

            $agent = $slot->getAgent();
            if (!$agent) {
                $this->addFlash('error', 'Ce créneau n\'est pas associé à un agent. Choisissez un autre créneau.');

                return $this->redirectToRoute('public_appointment', ['agent' => $selectedAgent->getId()]);
            }

            $appointment->setAgent($agent);

            $slot->setIsAvailable(false);
            $entityManager->persist($appointment);
            $entityManager->flush();

            $appointmentMailerService->sendConfirmation($appointment);
            $appointmentMailerService->notifyAgent($appointment);

            $this->addFlash('success', 'Votre rendez-vous a bien été enregistré. Un email de confirmation vous a été envoyé.');

            return $this->redirectToRoute('public_appointment', ['agent' => $selectedAgent->getId()]);
        }

        return $this->render('public/appointment/index.html.twig', [
            'form' => $form,
            'availableSlots' => $availableSlots,
            'agent' => $selectedAgent,
        ]);
    }
}
