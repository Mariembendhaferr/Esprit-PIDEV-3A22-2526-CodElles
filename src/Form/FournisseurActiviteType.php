<?php

namespace App\Form;

use App\Entity\Activite;
use App\Entity\FournisseurActivite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FournisseurActiviteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomFournisseur')
            ->add('emailFournisseur')
            ->add('telephoneFournisseur')
            ->add('adresseFournisseur')
            ->add('specialiteFournisseur', ChoiceType::class, [
                'label'       => 'Spécialité',
                'placeholder' => '-- Choisir une spécialité --',
                'choices'     => [
                    'Nature & Aventure'   => 'Nature & Aventure',
                    'Sports Extrêmes'     => 'Sports Extrêmes',
                    'Culture & Histoire'  => 'Culture & Histoire',
                    'Gastronomie'         => 'Gastronomie',
                    'Art & Musées'        => 'Art & Musées',
                    'Loisirs & Détente'   => 'Loisirs & Détente',
                    'Multi-activités'     => 'Multi-activités',
                ],
            ])
            ->add('websiteFournisseur')
            ->add('activites', EntityType::class, [
                'class'        => Activite::class,
                'choice_label' => 'nomActivite',  
                'multiple'     => true,
                'expanded'     => false,
                'label'        => 'Activités associées',
                'attr'         => [
                    'style' => 'height:120px; background:#FDF8F2; border:1.5px solid #E8D8C8; border-radius:10px; padding:8px; font-size:13px;',
                ],
                'help'         => 'Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs activités',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FournisseurActivite::class,
        ]);
    }
}
