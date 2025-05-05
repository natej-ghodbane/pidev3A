<?php

namespace App\Form;

use App\Entity\Destination;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class DestinationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomDestination', null, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nom de la destination ne peut pas être vide.',
                    ]),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description')
            ->add('imageDestination', FileType::class, [
                'label' => 'Image (JPEG, PNG, GIF)',
                'mapped' => false, // Important: This means it won't be mapped to the entity automatically
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'cette champ ne peut pas être vide.',
                    ]),
                    new File([
                        'maxSize' => '2M', // Limit file size to 2MB
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, GIF)',
                    ]),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('latitude')
            ->add('longitude')
            ->add('temperature')
            ->add('rate');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Destination::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
