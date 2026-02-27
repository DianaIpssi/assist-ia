<?php

namespace App\Controller\Public;

use App\Repository\PropertyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PropertyController extends AbstractController
{
    #[Route('/biens', name: 'public_property_index', methods: ['GET'])]
    public function index(PropertyRepository $repository): Response
    {
        $properties = $repository->findAll();

        return $this->render('public/property/index.html.twig', [
            'properties' => $properties,
        ]);
    }
}

