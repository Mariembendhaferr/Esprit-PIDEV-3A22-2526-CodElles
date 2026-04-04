<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Entity\Voyage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('voyage', EntityType::class, [
                'class' => Voyage::class,
                'choice_label' => 'destination',
                'label' => 'Voyage',
            ])
            ->add('dateDepart', DateType::class, [
                'label' => 'Date de départ',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('dateRetour', DateType::class, [
                'label' => 'Date de retour',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('nombrePersonnes', IntegerType::class, ['label' => 'Nombre de personnes'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}