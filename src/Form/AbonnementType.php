<?php

namespace App\Form;

use App\Entity\Abonnement;
use App\Entity\Pack;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints\NotBlank;

class AbonnementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('pack', EntityType::class, [
                'class' => Pack::class,
                'choice_label' => 'nom_pack', 
            ])
            ->add('date_Souscription', DateType::class, [
                'widget' => 'single_text',
                'required' => true,
                'label' => 'Date de souscription',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Obligatoire',
                    ]),
                ],
            ])
            ->add('date_Expiration', DateType::class, [
                'widget' => 'single_text',
                'required' => true,
                'label' => 'Date d\'expiration',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Obligatoire',
                    ]),
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Actif' => 'actif',
                    'Inactif' => 'inactif',
                    'Expire' => 'expire'
                ],
                'placeholder' => 'Sélectionnez un Statut',
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Abonnement::class,
            'attr' => ['novalidate' => 'novalidate'],
            'pack_ids' => [] // Expect an array of integer pack IDs
        ]);
    }
}
