<?php

namespace App\Form;

use App\Entity\Categorie;
use App\Entity\Partenaire;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File as AssertFile;
use Symfony\Component\Validator\Constraints\NotBlank;

class PartenaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit']; // 👈 option ajoutée
        $categories = $options['categories'];

        $builder
            ->add('nom', TextType::class)
            ->add('email', EmailType::class)
            ->add('adresse', TextType::class)
            ->add('description', TextareaType::class)
            ->add('date_ajout', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('id_categorie', EntityType::class, [
                'class' => Categorie::class,
                'choices' => $categories,
                'choice_label' => 'nom',
                'placeholder' => 'Choisir une catégorie',
                'required' => true,
            ])
            ->add('montant', IntegerType::class)
            ->add('logo', FileType::class, [
                'label' => 'Logo du Partenaire (JPEG, PNG)',
                'mapped' => false, // ⭐ Ne jamais mapper car c'est un FileType !
                'required' => false, // ⭐ Pas obligatoire même en ajout ou édition
                'constraints' => [
                    new AssertFile([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG).',
                    ])
                ],
            ])
            ->add('num_tel', TextType::class, [
                'label' => 'Numéro de Téléphone',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '+216 12 345 678']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Partenaire::class,
            'categories' => [],
            'is_edit' => false, // 👈 par défaut ce n'est pas une édition
        ]);
    }
}
