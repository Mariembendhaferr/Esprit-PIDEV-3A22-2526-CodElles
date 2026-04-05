<?php

namespace App\Form;

use App\Entity\Reclamation;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn (User $u) => $u->getPrenom().' '.$u->getNom(),
                'label' => 'Demandeur (nom & prénom)',
                'placeholder' => 'Sélectionner…',
                'required' => true,
                'attr' => ['class' => 'form-select admin-form-control'],
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('u')
                        ->orderBy('u.nom', 'ASC')
                        ->addOrderBy('u.prenom', 'ASC');
                },
            ])
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'empty_data' => '',
                'attr' => [
                    'class' => 'form-control admin-form-control',
                    'placeholder' => 'Objet de la réclamation',
                    'minlength' => 3,
                    'maxlength' => 150,
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'empty_data' => '',
                'attr' => [
                    'class' => 'form-control admin-form-control',
                    'rows' => 6,
                    'placeholder' => 'Détail de la demande…',
                    'minlength' => 10,
                    'maxlength' => 10000,
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En attente' => 'En attente',
                    'En cours' => 'En cours',
                    'Traité' => 'Traité',
                ],
                'attr' => ['class' => 'form-select admin-form-control'],
            ])
            ->add('priorite', ChoiceType::class, [
                'label' => 'Priorité',
                'choices' => [
                    'Faible' => 'Faible',
                    'Moyenne' => 'Moyenne',
                    'Élevée' => 'Élevée',
                ],
                'attr' => ['class' => 'form-select admin-form-control'],
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (!\is_array($data)) {
                return;
            }
            if (isset($data['titre']) && \is_string($data['titre'])) {
                $data['titre'] = trim($data['titre']);
            }
            if (isset($data['description']) && \is_string($data['description'])) {
                $data['description'] = trim($data['description']);
            }
            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
        ]);
    }
}
