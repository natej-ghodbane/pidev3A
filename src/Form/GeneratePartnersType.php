<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GeneratePartnersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('place', TextType::class, [
                'label' => 'Place',
                'attr' => ['placeholder' => 'Enter place (e.g., Tunis, Paris)']
            ])
            ->add('number', IntegerType::class, [
                'label' => 'Number of partners',
                'attr' => ['placeholder' => 'Enter number']
            ]);
    }
}
