<?php

namespace App\Controller\Agent;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/agent', name: 'agent_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('agent/dashboard/index.html.twig');
    }
}
