<?php

namespace App\Form;

use App\Entity\ReservationActivite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationActiviteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateActivite', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de l\'activité',
                'attr' => ['min' => (new \DateTime())->format('Y-m-d')]
            ])
            ->add('nombreParticipants', IntegerType::class, [
                'label' => 'Nombre de participants',
                'attr' => ['min' => 1]
            ]);
        // Ne pas ajouter user, activite, statut, dateReservation (gérés dans le contrôleur)
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReservationActivite::class,
        ]);
    }
}