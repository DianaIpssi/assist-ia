<?php

namespace App\Controller\Public;

use App\Repository\PropertyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SeoController extends AbstractController
{
    // Pages publiques listées dans le plan du site
    private const PUBLIC_ROUTES = ['app_home', 'public_property_index', 'public_appointment', 'public_contact', 'app_privacy'];

    #[Route('/robots.txt', name: 'seo_robots', methods: ['GET'])]
    // Fonction qui indique aux moteurs de recherche les pages à ne pas visiter
    public function robots(): Response
    {
        $content = "User-agent: *\n"
            ."Disallow: /agent\n"
            ."Disallow: /admin\n"
            ."Disallow: /property\n"
            ."Disallow: /login\n"
            ."Disallow: /api\n"
            .'Sitemap: '.$this->generateUrl('seo_sitemap', [], UrlGeneratorInterface::ABSOLUTE_URL)."\n";

        return new Response($content, Response::HTTP_OK, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    #[Route('/sitemap.xml', name: 'seo_sitemap', methods: ['GET'])]
    // Fonction qui liste les pages publiques et les biens publiés pour les moteurs de recherche
    public function sitemap(PropertyRepository $propertyRepository): Response
    {
        $urls = array_map(
            fn (string $route): string => $this->generateUrl($route, [], UrlGeneratorInterface::ABSOLUTE_URL),
            self::PUBLIC_ROUTES
        );

        foreach ($propertyRepository->findPublished() as $property) {
            $urls[] = $this->generateUrl('public_property_show', ['id' => $property->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        $response = $this->render('seo/sitemap.xml.twig', ['urls' => $urls]);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }
}
