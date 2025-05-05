<?php

namespace App\Form;

use App\Entity\Categorie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class CategorieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la catégorie',
                'attr' => ['class' => 'form-control'],
                'empty_data' => ''
                // Supprimer les contraintes ici
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control'],
                'empty_data' => ''
                // Supprimer les contraintes ici
            ])
            ->add('logo', FileType::class, [
                'label' => 'Logo',
                'required' => false,
                'mapped' => true,
                'data_class' => null,
                'constraints' => [
                    new File([
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier image valide (JPEG, PNG, WebP).',
                    ]),
                    new NotBlank([
                        'message' => 'Pas de logo '
                    ])
                ]
            ])
            ->add('nbrPartenaire', IntegerType::class, [
                'label' => 'Nombre de partenaires',
                'attr' => ['class' => 'form-control'],
                'required' => false, // Rend le champ facultatif
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez renseigner un nombre de partenaire.',
                    ]),
                    new GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Le nombre de partenaires ne peut pas être négatif.',
                    ]),
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Categorie::class,
            'allow_extra_fields' => true,
            'validation_groups' => ['Default'] // Ajout important
        ]);
    }
}