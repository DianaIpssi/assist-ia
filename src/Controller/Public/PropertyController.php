<?php

namespace App\Controller\Public;

use App\Entity\Property;
use App\Repository\PropertyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class PropertyController extends AbstractController
{
    // Types de biens proposés dans le filtre du catalogue
    public const PROPERTY_TYPES = [
        'studio' => 'Studio',
        't2' => 'T2',
        't3' => 'T3',
        't4' => 'T4',
        'maison' => 'Maison',
        'appartement' => 'Appartement',
    ];

    #[Route('/biens', name: 'public_property_index', methods: ['GET'])]
    // Fonction qui affiche le catalogue public des biens, filtré par ville, type et budget
    public function index(Request $request, PropertyRepository $repository): Response
    {
        $filters = $this->readFilters($request);

        return $this->render('public/property/index.html.twig', [
            'properties' => $repository->findPublishedByFilters($filters['budget'], $filters['ville'], $filters['type']),
            'filters' => $filters,
            'propertyTypes' => self::PROPERTY_TYPES,
            'hasFilters' => $filters['budget'] !== null || $filters['ville'] !== null || $filters['type'] !== null,
        ]);
    }

    #[Route('/biens/{id}', name: 'public_property_show', methods: ['GET'])]
    // Fonction qui affiche le détail d'un bien publié. 
    public function show(Property $property): Response
    {
        if (!$property->isPublished()) {
            throw new NotFoundHttpException('Ce bien n\'est pas disponible.');
        }

        return $this->render('public/property/show.html.twig', [
            'property' => $property,
        ]);
    }

    // Fonction qui lit et nettoie les filtres passés dans l'URL
    private function readFilters(Request $request): array
    {
        $city = trim((string) $request->query->get('ville', ''));
        $type = strtolower(trim((string) $request->query->get('type', '')));
        $budget = (int) $request->query->get('budget', 0);

        return [
            'ville' => $city !== '' ? mb_substr($city, 0, 120) : null,
            'type' => preg_match('/^[a-z0-9]{1,20}$/', $type) === 1 ? $type : null,
            'budget' => $budget > 0 ? $budget : null,
        ];
    }
}
