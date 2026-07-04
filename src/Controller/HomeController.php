<?php

namespace App\Controller;

use App\Repository\PropertyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    // Fonction qui affiche la page d'accueil avec les biens récents. 
    public function index(PropertyRepository $propertyRepository): Response
    {
        $featuredProperties = array_slice($propertyRepository->findPublished(), 0, 3);

        return $this->render('home/index.html.twig', [
            'featuredProperties' => $featuredProperties,
        ]);
    }
}
