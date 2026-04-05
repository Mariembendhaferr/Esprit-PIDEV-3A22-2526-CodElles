<?php

namespace App\Form;

use App\Entity\Activite;
use App\Entity\Avis;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AvisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn (User $u) => $u->getPrenom().' '.$u->getNom(),
                'label' => 'Voyageur (nom & prénom)',
                'placeholder' => 'Sélectionner…',
                'required' => true,
                'attr' => ['class' => 'form-select admin-form-control'],
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('u')
                        ->orderBy('u.nom', 'ASC')
                        ->addOrderBy('u.prenom', 'ASC');
                },
            ])
            ->add('activite', EntityType::class, [
                'class' => Activite::class,
                'choice_label' => 'nomActivite',
                'label' => 'Activité',
                'placeholder' => 'Sélectionner une activité',
                'required' => true,
                'attr' => ['class' => 'form-select admin-form-control'],
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('a')
                        ->orderBy('a.nomActivite', 'ASC');
                },
            ])
            ->add('note', ChoiceType::class, [
                'label' => 'Note',
                'required' => false,
                'placeholder' => 'Sans note',
                'empty_data' => null,
                'choices' => [
                    '1 étoile' => 1,
                    '2 étoiles' => 2,
                    '3 étoiles' => 3,
                    '4 étoiles' => 4,
                    '5 étoiles' => 5,
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'empty_data' => '',
                'attr' => [
                    'class' => 'form-control admin-form-control',
                    'rows' => 5,
                    'placeholder' => 'Avis détaillé…',
                    'minlength' => 10,
                    'maxlength' => 8000,
                ],
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (!\is_array($data)) {
                return;
            }
            if (isset($data['commentaire']) && \is_string($data['commentaire'])) {
                $data['commentaire'] = trim($data['commentaire']);
            }
            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Avis::class,
        ]);
    }
}
