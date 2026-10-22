<?php

namespace App\Controller\Admin;

use App\Entity\Agent;
use App\Entity\AvailabilitySlot;
use App\Entity\Property;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    // Fonction qui affiche la page demandée
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($adminUrlGenerator->setController(PropertyCrudController::class)->generateUrl());
    }

    // Fonction qui configure le tableau de bord admin
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle("<span style='font-weight:800;color:#e11d48'>Assist'IA — Admin</span>");
    }

    // Fonction qui définit le menu de l'admin
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToRoute('Retour espace agent', 'fas fa-arrow-left', 'agent_dashboard');
        yield MenuItem::section('Gestion globale');
        yield MenuItem::linkToCrud('Tous les biens', 'fas fa-building', Property::class);
        yield MenuItem::linkToCrud('Tous les créneaux', 'fas fa-calendar', AvailabilitySlot::class);
        yield MenuItem::linkToCrud('Agents', 'fas fa-user-tie', Agent::class);
    }
}
