<?php

namespace App\Controller\Admin;

use App\Entity\Property;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
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
            ->setPageTitle(Crud::PAGE_INDEX, 'Tous les biens')
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
            TextField::new('city', 'Ville'),
            AssociationField::new('agent', 'Agent responsable')->setRequired(true),
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'Brouillon' => Property::STATUS_DRAFT,
                    'Publié' => Property::STATUS_PUBLISHED,
                ])
                ->renderAsBadges([
                    Property::STATUS_DRAFT => 'secondary',
                    Property::STATUS_PUBLISHED => 'success',
                ]),
            TextareaField::new('description', 'Description détaillée')->hideOnIndex(),
        ];
    }
}
