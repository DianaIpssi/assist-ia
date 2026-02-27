<?php

namespace App\Controller\Admin;

use App\Entity\AvailabilitySlot;
use App\Entity\Property;
use App\Controller\Admin\PropertyCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
{
    $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
    return $this->redirect($adminUrlGenerator->setController(PropertyCrudController::class)->generateUrl());
}


    public function configureDashboard(): Dashboard
    {
    return Dashboard::new()
        ->setTitle("<span style='font-weight:800;color:#4f46e5'>Assist'IA</span>");
    }


    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');
    
        yield MenuItem::linkToCrud('Mes Biens', 'fas fa-building', Property::class);
    
        yield MenuItem::linkToCrud('Planning', 'fas fa-calendar', AvailabilitySlot::class);
    }
    
    
}
