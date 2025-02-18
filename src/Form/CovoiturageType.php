<?php

namespace App\Form;

use App\Entity\Covoiturage;
use App\Entity\Participe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CovoiturageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_depart', null, [
                'widget' => 'single_text'
            ])
            ->add('heure_depart', null, [
                'widget' => 'single_text'
            ])
            ->add('lieu_depart')
            ->add('date_arrivee', null, [
                'widget' => 'single_text'
            ])
            ->add('heure_arrivee', null, [
                'widget' => 'single_text'
            ])
            ->add('lieu_arrivee')
            ->add('statut')
            ->add('nb_place')
            ->add('prix_personne')
            ->add('participe', EntityType::class, [
                'class' => Participe::class,
'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Covoiturage::class,
        ]);
    }
}
