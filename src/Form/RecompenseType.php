<?php

namespace App\Form;

use App\Entity\Recompense;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class RecompenseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description')
            ->add('cout_en_points')
            ->add('disponibilite', ChoiceType::class, [
                'choices' => [
                    'Disponible' => 'disponible',
                    'Indisponible' => 'indisponible',
                ],
                'placeholder' => 'Sélectionner la disponibilité',
                'required' =>true,
                'attr' => ['class' => 'form-control']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recompense::class,
        ]);
    }
}
