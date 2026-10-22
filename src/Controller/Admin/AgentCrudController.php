<?php

namespace App\Controller\Admin;

use App\Entity\Agent;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AgentCrudController extends AbstractCrudController
{
    // Fonction qui reçoit le service de hachage des mots de passe
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Agent::class;
    }

    // Fonction qui configure l'affichage admin du CRUD
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Agent')
            ->setEntityLabelInPlural('Agents')
            ->setPageTitle(Crud::PAGE_INDEX, 'Agents de l\'agence')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un compte agent')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier l\'agent')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail de l\'agent')
            ->setDefaultSort(['fullName' => 'ASC']);
    }

    // Fonction qui retire la suppression pour ne pas perdre les biens et créneaux d'un agent
    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::DELETE);
    }

    // Fonction qui définit les champs affichés en admin
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('fullName', 'Nom complet');
        yield EmailField::new('email', 'Email de connexion');
        yield TextField::new('agencyName', 'Agence');
        yield ChoiceField::new('roles', 'Rôles')
            ->setChoices([
                'Agent' => 'ROLE_AGENT',
                'Administrateur' => 'ROLE_ADMIN',
            ])
            ->allowMultipleChoices()
            ->renderExpanded()
            ->renderAsBadges();

        // Le mot de passe n'est jamais affiché : il est seulement saisi puis haché
        $passwordConstraints = [new Length(min: 8, minMessage: 'Le mot de passe doit contenir au moins 8 caractères.')];
        if ($pageName === Crud::PAGE_NEW) {
            $passwordConstraints[] = new NotBlank(message: 'Le mot de passe est obligatoire.');
        }

        yield TextField::new('plainPassword', 'Mot de passe')
            ->setFormType(PasswordType::class)
            ->setFormTypeOptions([
                'mapped' => false,
                'required' => $pageName === Crud::PAGE_NEW,
                'constraints' => $passwordConstraints,
            ])
            ->setHelp($pageName === Crud::PAGE_EDIT ? 'Laisser vide pour garder le mot de passe actuel.' : '')
            ->onlyOnForms();

        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }

    // Fonction qui ajoute le hachage du mot de passe au formulaire de création
    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        return $this->addPasswordHashing(parent::createNewFormBuilder($entityDto, $formOptions, $context));
    }

    // Fonction qui ajoute le hachage du mot de passe au formulaire de modification
    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        return $this->addPasswordHashing(parent::createEditFormBuilder($entityDto, $formOptions, $context));
    }

    // Fonction qui hache le mot de passe saisi avant l'enregistrement
    private function addPasswordHashing(FormBuilderInterface $formBuilder): FormBuilderInterface
    {
        return $formBuilder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            if (!$form->isValid()) {
                return;
            }

            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword === null || $plainPassword === '') {
                return;
            }

            /** @var Agent $agent */
            $agent = $form->getData();
            $agent->setPassword($this->passwordHasher->hashPassword($agent, $plainPassword));
        });
    }
}
