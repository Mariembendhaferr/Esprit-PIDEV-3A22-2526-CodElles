<?php

namespace App\Form;

use App\Entity\Paiement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('modePaiement', ChoiceType::class, [
                'choices' => [
                    'Carte Bancaire' => 'Carte Bancaire',
                    'PayPal'         => 'PayPal',
                ],
                'expanded'    => true,
                'multiple'    => false,
                'label'       => 'Mode de paiement',
                'required'    => true,
                'constraints' => [new NotBlank(message: 'Veuillez choisir un mode de paiement.')],
            ])
            ->add('numeroCarte', TextType::class, [
                'label'    => 'Numéro de carte',
                'required' => false,
                'mapped'   => false,
                'attr'     => ['placeholder' => '1234 5678 9012 3456', 'maxlength' => 19],
            ])
            ->add('nomTitulaire', TextType::class, [
                'label'    => 'Nom du titulaire',
                'required' => false,
                'mapped'   => false,
                'attr'     => ['placeholder' => 'NOM Prénom'],
            ])
            ->add('expiration', TextType::class, [
                'label'    => "Date d'expiration (MM/AA)",
                'required' => false,
                'mapped'   => false,
                'attr'     => ['placeholder' => '12/26', 'maxlength' => 5],
            ])
            ->add('cvv', TextType::class, [
                'label'    => 'CVV',
                'required' => false,
                'mapped'   => false,
                'attr'     => ['placeholder' => '123', 'maxlength' => 3],
            ])
        ;

        // Validation côté serveur selon le mode de paiement choisi
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $mode = $form->get('modePaiement')->getData();

            if ($mode === 'Carte Bancaire') {
                $numeroCarte  = trim($form->get('numeroCarte')->getData() ?? '');
                $nomTitulaire = trim($form->get('nomTitulaire')->getData() ?? '');
                $expiration   = trim($form->get('expiration')->getData() ?? '');
                $cvv          = trim($form->get('cvv')->getData() ?? '');

                // Numéro de carte : 16 chiffres (avec ou sans espaces)
                $numeroClean = preg_replace('/\s+/', '', $numeroCarte);
                if (empty($numeroCarte)) {
                    $form->get('numeroCarte')->addError(new \Symfony\Component\Form\FormError('Le numéro de carte est obligatoire.'));
                } elseif (!preg_match('/^\d{16}$/', $numeroClean)) {
                    $form->get('numeroCarte')->addError(new \Symfony\Component\Form\FormError('Le numéro de carte doit contenir 16 chiffres.'));
                }

                if (empty($nomTitulaire)) {
                    $form->get('nomTitulaire')->addError(new \Symfony\Component\Form\FormError('Le nom du titulaire est obligatoire.'));
                }

                // Expiration : MM/AA
                if (empty($expiration)) {
                    $form->get('expiration')->addError(new \Symfony\Component\Form\FormError("La date d'expiration est obligatoire."));
                } elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiration)) {
                    $form->get('expiration')->addError(new \Symfony\Component\Form\FormError("Format invalide. Utilisez MM/AA."));
                } else {
                    [$mm, $aa] = explode('/', $expiration);
                    $expYear  = (int)('20'.$aa);
                    $expMonth = (int)$mm;
                    $now = new \DateTime();
                    if ($expYear < (int)$now->format('Y') ||
                        ($expYear === (int)$now->format('Y') && $expMonth < (int)$now->format('m'))) {
                        $form->get('expiration')->addError(new \Symfony\Component\Form\FormError('La carte est expirée.'));
                    }
                }

                // CVV : 3 chiffres
                if (empty($cvv)) {
                    $form->get('cvv')->addError(new \Symfony\Component\Form\FormError('Le CVV est obligatoire.'));
                } elseif (!preg_match('/^\d{3}$/', $cvv)) {
                    $form->get('cvv')->addError(new \Symfony\Component\Form\FormError('Le CVV doit contenir exactement 3 chiffres.'));
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Paiement::class,
        ]);
    }
}