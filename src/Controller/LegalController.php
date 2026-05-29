<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LegalController extends AbstractController
{
    #[Route('/politique-confidentialite', name: 'app_privacy')]
    /** Fonction qui affiche la page de politique de confidentialité. */
    public function privacy(): Response
    {
        return $this->render('legal/privacy.html.twig');
    }
}
