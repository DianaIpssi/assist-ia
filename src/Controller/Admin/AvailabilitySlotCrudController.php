<?php

namespace App\Controller\Admin;

use App\Entity\AvailabilitySlot;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AvailabilitySlotCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AvailabilitySlot::class;
    }

    // Fonction qui définit les champs affichés en admin
    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('agent', 'Agent');
        yield DateTimeField::new('startAt', 'Début');
        yield DateTimeField::new('endAt', 'Fin');
        yield BooleanField::new('isAvailable', 'Disponible');
        yield TextField::new('note', 'Note');
    }
}
