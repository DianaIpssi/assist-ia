<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Agent;
use App\Entity\Property;
use App\Tests\DatabaseWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SeoControllerTest extends DatabaseWebTestCase
{
    // Fonction qui vérifie que robots.txt exclut les espaces privés et indique le plan du site
    public function testRobotsExcludesPrivateAreas(): void
    {
        $client = $this->createClientWithFreshDatabase();

        $client->request('GET', '/robots.txt');

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('Disallow: /agent', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Sitemap: http://localhost/sitemap.xml', $content);
    }

    // Fonction qui vérifie que le plan du site liste les biens publiés, mais pas les brouillons
    public function testSitemapListsOnlyPublishedProperties(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $published = $this->seedProperty('T2 avec balcon', Property::STATUS_PUBLISHED);
        $draft = $this->seedProperty('Maison en brouillon', Property::STATUS_DRAFT);

        $client->request('GET', '/sitemap.xml');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/xml; charset=UTF-8');
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('<loc>http://localhost/biens</loc>', $content);
        $this->assertStringContainsString('<loc>http://localhost/biens/'.$published->getId().'</loc>', $content);
        $this->assertStringNotContainsString('/biens/'.$draft->getId().'<', $content);
    }

    // Fonction qui vérifie qu'une fiche bien a sa propre description pour les moteurs de recherche
    public function testPropertyPageHasMetaDescription(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $property = $this->seedProperty('T2 avec balcon', Property::STATUS_PUBLISHED);

        $crawler = $client->request('GET', '/biens/'.$property->getId());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('T2 avec balcon à Lyon', $crawler->filter('meta[name="description"]')->attr('content'));
    }

    // Fonction qui crée un bien de test avec son agent
    private function seedProperty(string $title, string $status): Property
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $agent = (new Agent())
            ->setEmail(uniqid('agent-', true).'@assist-ia.fr')
            ->setFullName('Agent Test')
            ->setRoles(['ROLE_AGENT']);
        $agent->setPassword($hasher->hashPassword($agent, 'Agent123!'));

        $property = (new Property())
            ->setTitle($title)
            ->setPrice(180000)
            ->setCity('Lyon')
            ->setDescription('Appartement lumineux avec balcon, proche centre-ville.')
            ->setStatus($status)
            ->setAgent($agent);

        $entityManager->persist($agent);
        $entityManager->persist($property);
        $entityManager->flush();

        return $property;
    }
}
