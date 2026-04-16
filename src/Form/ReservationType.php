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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('voyage', EntityType::class, [
                'class' => Voyage::class,
                'choice_label' => 'destination',
                'label' => 'Voyage',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner une destination.']),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateDepart', DateType::class, [
                'label' => 'Date de départ',
                'widget' => 'single_text',
                'html5' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de départ est obligatoire.']),
                    new Assert\GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date de départ doit être aujourd\'hui ou dans le futur.'
                    ]),
                ],
                'attr' => [
                    'min' => (new \DateTime())->format('Y-m-d'),
                    'class' => 'form-control'
                ]
            ])
            ->add('dateRetour', DateType::class, [
                'label' => 'Date de retour',
                'widget' => 'single_text',
                'html5' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de retour est obligatoire.']),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('nombrePersonnes', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nombre de personnes est obligatoire.']),
                    new Assert\Positive(['message' => 'Le nombre de personnes doit être au moins 1.']),
                    new Assert\LessThanOrEqual([
                        'value' => 20,
                        'message' => 'Maximum 20 personnes par réservation.'
                    ]),
                ],
                'attr' => [
                    'min' => 1,
                    'max' => 20,
                    'class' => 'form-control'
                ]
            ])
                        // Ajoutez ce champ dans la méthode buildForm()
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En attente' => 'en attente',
                    'Payé' => 'payé',
                    'Annulé' => 'annulé',
                ],
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'constraints' => [
                new Callback(function($reservation, $context) {
                    $dateDepart = $reservation->getDateDepart();
                    $dateRetour = $reservation->getDateRetour();
                    
                    if ($dateDepart && $dateRetour && $dateRetour <= $dateDepart) {
                        $context->buildViolation('La date de retour doit être après la date de départ.')
                            ->atPath('dateRetour')
                            ->addViolation();
                    }
                }),
            ],
        ]);
    }
}