<?php

namespace App\Controller\Public;

use App\Entity\Property;
use App\Repository\PropertyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class PropertyController extends AbstractController
{
    #[Route('/biens', name: 'public_property_index', methods: ['GET'])]
    public function index(PropertyRepository $repository): Response
    {
        return $this->render('public/property/index.html.twig', [
            'properties' => $repository->findPublished(),
        ]);
    }

    #[Route('/biens/{id}', name: 'public_property_show', methods: ['GET'])]
    public function show(Property $property): Response
    {
        if (!$property->isPublished()) {
            throw new NotFoundHttpException('Ce bien n\'est pas disponible.');
        }

        return $this->render('public/property/show.html.twig', [
            'property' => $property,
        ]);
    }
}
