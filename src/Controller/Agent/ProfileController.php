<?php

namespace App\Controller\Agent;

use App\Entity\Agent;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    #[Route('/agent/profil', name: 'agent_profile', methods: ['GET', 'POST'])]
    // Fonction qui permet à l'agent de modifier son nom et son agence
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $agent = $this->getUser();
        if (!$agent instanceof Agent) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ProfileType::class, $agent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Votre profil a bien été mis à jour.');

            return $this->redirectToRoute('agent_profile');
        }

        return $this->render('agent/profile/edit.html.twig', [
            'form' => $form,
        ]);
    }
}
