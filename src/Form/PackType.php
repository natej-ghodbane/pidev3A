<?php

namespace App\Form;

use App\Entity\Pack;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom_pack', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom du pack est obligatoire.']),
                    new Assert\Regex([
                        'pattern' => '/^[A-Z].*/',
                        'message' => 'Le nom du pack doit commencer par une majuscule.'
                    ])
                ],
            ])
            ->add('description', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La description est obligatoire.']),
                    new Assert\Length([
                        'max' => 20,
                        'maxMessage' => 'La description ne doit pas dépasser 20 caractères.'
                    ])
                ],
            ])
            ->add('prix', NumberType::class, [
                'constraints' => [
                    new Assert\NotNull(['message' => 'Le prix est obligatoire.']),
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Le prix ne peut pas être négatif.'
                    ])
                ],
            ])
            ->add('duree', NumberType::class, [
                'constraints' => [
                    new Assert\NotNull(['message' => 'La durée est obligatoire.']),
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'La durée ne peut pas être négative.'
                    ])
                ],
            ])
            ->add('avantages', ChoiceType::class, [
                'choices' => [
                    'Aventure' => 'Aventure',
                    'Nature' => 'Nature',
                    'Gastronomie' => 'Gastronomie'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Les avantages sont obligatoires.']),
                    new Assert\Choice([
                        'choices' => ['Aventure', 'Nature', 'Gastronomie'],
                        'message' => 'Les avantages doivent être "Aventure", "Nature" ou "Gastronomie".'
                    ])
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Expiré' => 'Expiré',
                    'Actif' => 'Actif'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le statut est obligatoire.']),
                    new Assert\Choice([
                        'choices' => ['Expiré', 'Actif'],
                        'message' => 'Le statut doit être "Expiré" ou "Actif".'
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pack::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
