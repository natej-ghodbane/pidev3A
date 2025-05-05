<?php

namespace App\Form;

use App\Entity\Reclamation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeRec', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Problème technique' => 'Problème technique',
                    'Problème de paiement' => 'Problème de paiement',
                    'Problème de réservation' => 'Problème de réservation',
                    'Autre' => 'Autre'
                ],
                'attr' => [
                    'class' => 'form-control',
                    'novalidate' => 'novalidate'
                ]
            ])
            ->add('descriptionRec', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'novalidate' => 'novalidate'
                ]
            ])
            /*->add('etatRec', ChoiceType::class, [
                'label' => 'État',
                'choices' => [
                    'En cours' => 'En cours',
                    'Résolue' => 'Résolue',
                    'Rejetée' => 'Rejetée'
                ],
                'attr' => [
                    'class' => 'form-control',
                    'novalidate' => 'novalidate'
                ]
            ])*/
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
            'attr' => ['novalidate' => 'novalidate']
        ]);
    }
}
