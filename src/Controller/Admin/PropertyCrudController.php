<?php

namespace App\Controller\Admin;

use App\Entity\Property;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PropertyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Property::class;
    }

    // Fonction qui configure l'affichage admin du CRUD
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Bien')
            ->setEntityLabelInPlural('Biens')
            ->setPageTitle(Crud::PAGE_INDEX, 'Mes biens')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter un bien')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier le bien')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail du bien');
    }

    // Fonction qui définit les champs affichés en admin
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title', "Titre de l'annonce"),
            IntegerField::new('price', 'Prix (€)'),
            TextareaField::new('description', 'Description détaillée')->hideOnIndex(),
        ];
    }
}
