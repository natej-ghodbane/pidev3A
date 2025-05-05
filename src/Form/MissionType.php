<?php

namespace App\Form;

use App\Entity\Mission;
use App\Entity\Recompense;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository; // Ne pas oublier d'importer cette classe

class MissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id_recompense', EntityType::class, [
                'class' => Recompense::class,
                'choice_label' => 'description',
                'placeholder' => 'Sélectionner une récompense',
                'attr' => ['class' => 'form-control'],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('r')
                        ->where('r.disponibilite = :disponibilite')  // Filtrer par la valeur 'disponible'
                        ->setParameter('disponibilite', 'disponible'); // Utiliser la valeur de statut "disponible"
                }
            ])
            ->add('description')
            ->add('points_recompense')
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'En cours' => 'en cours',
                    'Valide' => 'valide',
                ],
                'placeholder' => 'Choisissez un statut',
                'required' => true,
                'data' => $options['data']->getStatut(),
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mission::class,
        ]);
    }
}
