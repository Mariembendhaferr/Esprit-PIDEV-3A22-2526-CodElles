<?php
// src/Form/SearchDestinationType.php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchDestinationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('destination', TextType::class, [
            'label' => 'Where to?',
            'attr' => [
                'placeholder' => 'Paris, Tunis, Dubai...',
                'class' => 'form-control',
                'data-autocomplete-url' => $options['autocomplete_url'],
                'data-autocomplete-min-length' => 2,
            ],
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'autocomplete_url' => '',
        ]);
    }
}