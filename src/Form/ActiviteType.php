<?php

namespace App\Form;

use App\Entity\Activite;
use App\Entity\FournisseurActivite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ActiviteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomActivite', TextType::class, [
                'label' => 'Nom de l\'activité',
                'attr'  => ['placeholder' => 'Ex: Snorkeling à Hawaii'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire']),
                    new Assert\Length(['min' => 3, 'minMessage' => 'Minimum {{ limit }} caractères']),
                    new Assert\Regex(['pattern' => '/^[^0-9]*$/', 'message' => 'Le nom ne doit pas contenir de chiffres']),
                ],
            ])
            ->add('descriptionActivite', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => ['placeholder' => 'Décrivez l\'activité...', 'rows' => 3],
                'constraints' => [
                    new Assert\Length(['max' => 500, 'maxMessage' => 'Maximum {{ limit }} caractères']),
                ],
            ])
            ->add('categorieActivite', ChoiceType::class, [
                'label'       => 'Catégorie',
                'placeholder' => '-- Choisir une catégorie --',
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
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez choisir une catégorie']),
                ],
            ])
            ->add('coutActivite', NumberType::class, [
                'label' => 'Prix (DT)',
                'attr'  => ['placeholder' => 'Ex: 50.00'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prix est obligatoire']),
                    new Assert\Positive(['message' => 'Le prix doit être positif']),
                    new Assert\LessThanOrEqual(['value' => 99999, 'message' => 'Prix maximum : 99 999 DT']),
                ],
            ])
            ->add('dureeActivite', IntegerType::class, [
                'label' => 'Durée (minutes)',
                'attr'  => ['placeholder' => 'Ex: 90', 'min' => 1],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La durée est obligatoire']),
                    new Assert\Positive(['message' => 'La durée doit être positive']),
                    new Assert\LessThanOrEqual(['value' => 1440, 'message' => 'Maximum 1440 min (24h)']),
                ],
            ])
            ->add('capaciteMaxActivite', IntegerType::class, [
                'label'    => 'Capacité max (personnes)',
                'required' => false,
                'attr'     => ['placeholder' => 'Ex: 20', 'min' => 1],
                'constraints' => [
                    new Assert\Positive(['message' => 'La capacité doit être positive']),
                ],
            ])
            ->add('localisationActivite', TextType::class, [
                'label'    => 'Destination',
                'required' => false,
                'attr'     => ['placeholder' => 'Ex: Paris, France', 'autocomplete' => 'off'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La destination est obligatoire']),
                ],
            ])
            ->add('imageActivite', TextType::class, [
                'label'    => 'URL de l\'image',
                'required' => false,
                'attr'     => ['placeholder' => 'https://exemple.com/image.jpg'],
                'constraints' => [
                    new Assert\Url(['message' => 'L\'URL n\'est pas valide']), // ✅ SANS requireTld
                ],
            ])
            ->add('fournisseurs', EntityType::class, [
                'class'        => FournisseurActivite::class,
                'choice_label' => 'nomFournisseur',
                'multiple'     => true,
                'required'     => false,
                'label'        => 'Fournisseurs',
                'attr'         => ['style' => 'height:110px;'],
            ])
            ->add('disponibiliteActivite', CheckboxType::class, [
                'label'    => 'Disponible',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Activite::class]);
    }
}