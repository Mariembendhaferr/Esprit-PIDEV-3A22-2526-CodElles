<?php
// src/Form/CommunityPostType.php

namespace App\Form;

use App\Entity\CommunityPost;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class CommunityPostType extends AbstractType
{
    // ── Correspondance continent → destinations autorisées ────────────────────
    public const DESTINATIONS = [
        'afrique' => [
            'Tunisie', 'Algérie', 'Maroc', 'Égypte', 'Kenya', 'Tanzanie',
            'Afrique du Sud', 'Sénégal', 'Côte d\'Ivoire', 'Madagascar',
            'Éthiopie', 'Ghana', 'Mozambique', 'Namibie', 'Rwanda',
        ],
        'asie' => [
            'Japon', 'Thaïlande', 'Bali (Indonésie)', 'Vietnam', 'Chine',
            'Inde', 'Dubaï (Émirats)', 'Turquie', 'Corée du Sud', 'Maldives',
            'Sri Lanka', 'Népal', 'Jordanie', 'Géorgie', 'Philippines',
        ],
        'europe' => [
            'France', 'Italie', 'Espagne', 'Grèce', 'Portugal',
            'Suisse', 'Autriche', 'Croatie', 'Norvège', 'Islande',
            'Pays-Bas', 'Allemagne', 'Royaume-Uni', 'Irlande', 'Suède',
        ],
        'amerique' => [
            'États-Unis', 'Mexique', 'Brésil', 'Argentine', 'Colombie',
            'Pérou', 'Canada', 'Cuba', 'Costa Rica', 'Chili',
            'Équateur', 'Bolivie', 'République Dominicaine', 'Jamaïque', 'Panama',
        ],
        'oceanie' => [
            'Australie', 'Nouvelle-Zélande', 'Fidji', 'Tahiti (Polynésie française)',
            'Papouasie-Nouvelle-Guinée', 'Vanuatu', 'Samoa', 'Tonga',
        ],
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('continent', ChoiceType::class, [
                'label'       => false,
                'placeholder' => 'Choisir un continent',
                'choices'     => [
                    'Afrique'  => 'afrique',
                    'Asie'     => 'asie',
                    'Europe'   => 'europe',
                    'Amérique' => 'amerique',
                    'Océanie'  => 'oceanie',
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez choisir un continent.'),
                ],
                'attr' => [
                    'class' => 'field-select',
                    'id'    => 'community_post_continent',
                ],
            ])

            ->add('destination', ChoiceType::class, [
                'label'       => false,
                'placeholder' => 'Choisissez d\'abord un continent',
                // ✅ Choix vides au départ, remplis par PRE_SUBMIT côté serveur
                // et par JS côté client — PAS de disabled ici
                'choices'     => [],
                'required'    => true,
                'constraints' => [
                    new NotBlank(message: 'Veuillez choisir une destination.'),
                ],
                'attr' => [
                    'class' => 'field-select',
                    'id'    => 'community_post_destination',
                ],
            ])

            ->add('photo', FileType::class, [
                'label'    => false,
                'mapped'   => false,
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Veuillez ajouter une photo.'),
                    new File([
                        'maxSize'          => '5M',
                        'maxSizeMessage'   => 'La photo ne doit pas dépasser 5 Mo.',
                        'mimeTypes'        => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Formats acceptés : JPG, PNG, WEBP.',
                    ]),
                ],
                'attr' => [
                    'accept' => 'image/*',
                    'id'     => 'community_post_photo',
                    'class'  => 'file-input-hidden',
                ],
            ]);

        // ── PRE_SUBMIT : reconstruire destination avec les bons choix ─────────
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data      = $event->getData();
            $form      = $event->getForm();
            $continent = $data['continent'] ?? null;

            $allowed = self::DESTINATIONS[$continent] ?? [];

            $form->remove('destination');
            $form->add('destination', ChoiceType::class, [
                'label'       => false,
                'placeholder' => 'Choisir une destination',
                // ✅ array_combine pour que valeur = label
                'choices'     => array_combine($allowed, $allowed),
                'required'    => true,
                'constraints' => [
                    new NotBlank(message: 'Veuillez choisir une destination.'),
                ],
                'attr' => [
                    'class' => 'field-select',
                    'id'    => 'community_post_destination',
                ],
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'      => CommunityPost::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'community_post',
        ]);
    }
}
