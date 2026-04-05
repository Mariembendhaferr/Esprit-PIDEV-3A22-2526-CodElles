<?php

namespace App\Form;

use App\Entity\Reclamation;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReclamationPublicType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn (User $u) => $u->getPrenom().' '.$u->getNom(),
                'label' => 'Demandeur (nom et prénom)',
                'placeholder' => 'Choisir dans la liste…',
                'required' => true,
                'attr' => ['class' => 'form-select reclamation-form-control'],
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('u')
                        ->orderBy('u.nom', 'ASC')
                        ->addOrderBy('u.prenom', 'ASC');
                },
            ])
            ->add('titre', TextType::class, [
                'label' => 'Objet de la réclamation',
                'empty_data' => '',
                'attr' => [
                    'class' => 'form-control reclamation-form-control',
                    'placeholder' => 'Ex. : problème de réservation',
                    'minlength' => 3,
                    'maxlength' => 150,
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description détaillée',
                'empty_data' => '',
                'attr' => [
                    'class' => 'form-control reclamation-form-control',
                    'rows' => 6,
                    'placeholder' => 'Décrivez votre demande en détail…',
                    'minlength' => 10,
                    'maxlength' => 10000,
                ],
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
