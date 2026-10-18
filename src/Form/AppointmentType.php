<?php

namespace App\Form;

use App\Entity\Appointment;
use App\Entity\AvailabilitySlot;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppointmentType extends AbstractType
{
    // Fonction qui construit les champs du formulaire
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('slot', EntityType::class, [
                'class' => AvailabilitySlot::class,
                'choices' => $options['available_slots'],
                'choice_label' => static fn (AvailabilitySlot $slot): string => sprintf(
                    '%s de %s à %s',
                    $slot->getStartAt()?->format('d/m/Y'),
                    $slot->getStartAt()?->format('H:i'),
                    $slot->getEndAt()?->format('H:i')
                ),
                'placeholder' => 'Choisissez un créneau',
                'label' => 'Créneau disponible',
            ])
            ->add('clientName', TextType::class, [
                'label' => 'Votre nom',
            ])
            ->add('clientEmail', EmailType::class, [
                'label' => 'Votre email',
            ])
            ->add('clientPhone', TelType::class, [
                'required' => false,
                'label' => 'Téléphone',
            ])
        ;
    }

    // Fonction qui configure les options du formulaire
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
            'available_slots' => [],
        ]);
    }
}
