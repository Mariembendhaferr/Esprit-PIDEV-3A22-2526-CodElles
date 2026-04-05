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
use Symfony\Component\Validator\Constraints as Assert;

class VoyageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('titre', TextType::class, [
                'label' => false,
                'attr'  => [
                    'id'          => 'titre',          // ✅ JS: getElementById('titre')
                    'class'       => 'luxury-input',
                    'placeholder' => 'ex: Symphonie des Fjords de Glace',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre est obligatoire.']),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\-\']+$/u',
                        'message' => 'Le titre doit contenir uniquement des lettres.',
                    ]),
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
                    'id'    => 'continent',             // ✅ JS: getElementById('continent')
                    'class' => 'luxury-input luxury-select',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner un continent valide.']),
                    new Assert\Choice([
                        'choices' => ['Afrique', 'Europe', 'Asie', 'Amériques', 'Océanie'],
                        'message' => 'Veuillez sélectionner un continent valide.',
                    ]),
                ],
            ])

            ->add('destination', TextType::class, [
                'label' => false,
                'attr'  => [
                    'id'           => 'destination',   // ✅ JS: getElementById('destination')
                    'class'        => 'luxury-input',
                    'placeholder'  => 'ex: Maroc, Japon, Norvège...',
                    'autocomplete' => 'off',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La destination est obligatoire.']),
                ],
            ])

            ->add('budget_estime', NumberType::class, [
                'label' => false,
                'attr'  => [
                    'id'          => 'budget_estime',  // ✅ CORRIGÉ (était 'budget') — JS: getElementById('budget_estime')
                    'class'       => 'luxury-input',
                    'placeholder' => '3500',
                    'min'         => 1,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le budget est obligatoire.']),
                    new Assert\GreaterThan([
                        'value'   => 0,
                        'message' => 'Le budget doit être un entier supérieur à 0.',
                    ]),
                ],
            ])

            ->add('duree', IntegerType::class, [
                'label' => false,
                'attr'  => [
                    'id'          => 'duree',          // ✅ JS: getElementById('duree')
                    'class'       => 'luxury-input',
                    'placeholder' => '7',
                    'min'         => 1,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La durée est obligatoire.']),
                    new Assert\GreaterThan([
                        'value'   => 0,
                        'message' => 'La durée doit être un entier supérieur à 0.',
                    ]),
                ],
            ])

            ->add('nb_personnes', IntegerType::class, [
                'label' => false,
                'attr'  => [
                    'id'          => 'nb_personnes',   // ✅ CORRIGÉ (était 'nb_pers') — JS: getElementById('nb_personnes')
                    'class'       => 'luxury-input',
                    'placeholder' => '2',
                    'min'         => 1,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nombre de personnes est obligatoire.']),
                    new Assert\GreaterThan([
                        'value'   => 0,
                        'message' => 'Le nombre de personnes doit être un entier supérieur à 0.',
                    ]),
                ],
            ])

            ->add('image_url', TextType::class, [
                'label'    => false,
                'required' => false,
                'attr'     => [
                    'id'          => 'image_url',      // ✅ CORRIGÉ (était 'photo_url') — JS: getElementById('image_url')
                    'class'       => 'luxury-input',
                    'placeholder' => 'https://images.unsplash.com/photo-xxx.jpg',
                ],
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/\.(jpg|jpeg|png)$/i',
                        'message' => "L'URL de l'image doit se terminer par .jpg, .jpeg ou .png.",
                    ]),
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
