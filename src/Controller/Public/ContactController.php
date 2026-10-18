<?php

namespace App\Controller\Public;

use App\Form\ContactType;
use App\Repository\PropertyRepository;
use App\Service\ContactMailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'public_contact')]
    // Fonction qui affiche et traite le formulaire de contact. 
    public function index(
        Request $request,
        PropertyRepository $propertyRepository,
        ContactMailerService $contactMailerService,
    ): Response {
        $property = null;
        $propertyId = $request->query->getInt('bien');
        if ($propertyId > 0) {
            $found = $propertyRepository->find($propertyId);
            $property = $found !== null && $found->isPublished() ? $found : null;
        }

        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $contactMailerService->send($data['name'], $data['email'], $data['message'], $property);

            $this->addFlash('success', $property !== null
                ? 'Merci pour votre message. L\'agent responsable de ce bien vous recontactera rapidement.'
                : 'Merci pour votre message. Un agent vous recontactera rapidement.');

            return $this->redirectToRoute('public_contact', $property !== null ? ['bien' => $property->getId()] : []);
        }

        return $this->render('public/contact/index.html.twig', [
            'form' => $form,
            'property' => $property,
        ]);
    }
}
