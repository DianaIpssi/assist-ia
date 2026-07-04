<?php

namespace App\Controller\Public;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'public_contact')]
    // Fonction qui affiche et traite le formulaire de contact. 
    public function index(Request $request): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', 'Merci pour votre message. Un agent vous recontactera rapidement.');

            return $this->redirectToRoute('public_contact');
        }

        return $this->render('public/contact/index.html.twig', [
            'form' => $form,
        ]);
    }
}
