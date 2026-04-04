<?php

namespace App\Form;

use App\Entity\Activite;
use App\Entity\FournisseurActivite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActiviteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
$builder
    ->add('nomActivite')
    ->add('descriptionActivite')
    ->add('categorieActivite', ChoiceType::class, [
        'choices' => [
            'Sports'        => 'sports',
            'Culture'       => 'culture',
            'Nature'        => 'nature',
            'Aventure'      => 'aventure',
            'Gastronomie'   => 'gastronomie',
            'Bien-être'     => 'bien-etre',
            'Plage'         => 'plage',
            'Visite guidée' => 'visite',
        ],
    ])
    ->add('coutActivite')
            ->add('dureeActivite')
            ->add('disponibiliteActivite')
            ->add('localisationActivite')
            ->add('capaciteMaxActivite')
            ->add('imageActivite')
            ->add('latitudeActivite')
            ->add('longitudeActivite')
            ->add('fournisseurs', EntityType::class, [
                'class' => FournisseurActivite::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activite::class,
        ]);
    }
}
