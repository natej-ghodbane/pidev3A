<?php
namespace App\Form;

use App\Entity\Activite;
use App\Entity\Destination;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class ActiviteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $destinations = $options['destinations'];  // Get the destinations passed from the controller

        $builder
            ->add('id_destination', EntityType::class, [
                'class' => Destination::class,
                'choice_label' => 'nomDestination',
                'label' => 'Destination',
                'placeholder' => 'Choisir une destination',
                'required' => true,
            ])
            ->add('nom_activite', null, [
                'label' => 'Nom de l\'Activité', // Custom label
                'attr' => ['class' => 'form-control'], // Styling the field
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de l\'Activité', // Custom label
                'attr' => ['class' => 'form-control'], // Styling the field
            ])
            ->add('heure', TextType::class, [
                'label' => 'Heure de l\'Activité', // Custom label
                'attr' => ['class' => 'form-control', 'placeholder' => 'HH:mm'], // Styling the field and placeholder
                'required' => true, // Make sure it's required
                'constraints' => [
                    new Regex([
                        'pattern' => '/^([01]?[0-9]|2[0-3]):([0-5][0-9])$/',
                        'message' => 'Please enter the time in HH:mm format.'
                    ])
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Active' => 'active',
                    'Inactive' => 'inactive',
                    'Completed' => 'completed',
                ],
                'label' => 'Statut', // Custom label
                'attr' => ['class' => 'form-control'], // Styling the field
            ]);
    }

    private function getDestinationChoices(array $destinations): array
    {
        $choices = [];
        foreach ($destinations as $destination) {
            $choices[$destination->getNomDestination()] = $destination->getId();
        }

        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activite::class,
            'attr' => ['novalidate' => 'novalidate'],
            'destinations' => [],  // Define the 'destinations' option
        ]);
    }
}
