<?php

namespace App\Form;

use App\Entity\Voyage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VoyageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => false,
                'attr'  => [
                    'id'          => 'titre',
                    'class'       => 'luxury-input',
                    'placeholder' => 'ex: Symphonie des Fjords de Glace (minimum 25 caractères)',
                ],
            ])

            ->add('continent', ChoiceType::class, [
                'label'       => false,
                'placeholder' => 'Choisir...',
                'choices'     => [
                    'Afrique'   => 'Afrique',
                    'Europe'    => 'Europe',
                    'Asie'      => 'Asie',
                    'Amériques' => 'Amériques',
                    'Océanie'   => 'Océanie',
                ],
                'attr' => [
                    'id'    => 'continent',
                    'class' => 'luxury-input luxury-select',
                ],
            ])

            ->add('destination', TextType::class, [
                'label' => false,
                'attr'  => [
                    'id'           => 'destination',
                    'class'        => 'luxury-input',
                    'placeholder'  => 'ex: Maroc, Japon, Norvège...',
                    'autocomplete' => 'off',
                ],
            ])

            ->add('budget_estime', NumberType::class, [
                'label' => false,
                'attr'  => [
                    'id'          => 'budget_estime',
                    'class'       => 'luxury-input',
                    'placeholder' => '3500',
                    'min'         => 1,
                ],
            ])

            ->add('duree', IntegerType::class, [
                'label' => false,
                'attr'  => [
                    'id'          => 'duree',
                    'class'       => 'luxury-input',
                    'placeholder' => '7',
                    'min'         => 1,
                ],
            ])

            ->add('nb_personnes', IntegerType::class, [
                'label' => false,
                'attr'  => [
                    'id'          => 'nb_personnes',
                    'class'       => 'luxury-input',
                    'placeholder' => '2',
                    'min'         => 1,
                ],
            ])

            ->add('image_url', TextType::class, [
                'label'    => false,
                'required' => false,
                'attr'     => [
                    'id'          => 'image_url',
                    'class'       => 'luxury-input',
                    'placeholder' => 'https://images.unsplash.com/photo-xxx.jpg',
                ],
            ])

            ->add('description', TextareaType::class, [
                'label'    => false,
                'required' => false,
                'attr'     => [
                    'id'          => 'description',
                    'class'       => 'luxury-textarea',
                    'placeholder' => 'Rédigez ici le rêve que vous vendez...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Voyage::class,
        ]);
    }
}