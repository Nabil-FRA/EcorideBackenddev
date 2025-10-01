<?php

namespace App\Form;

use App\Entity\Configuration;
use App\Entity\Dispose;
use App\Entity\Parametre;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParametreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('propriete')
            ->add('valeur')
            ->add('dispose', EntityType::class, [
                'class' => Dispose::class,
'choice_label' => 'id',
            ])
            ->add('configuration', EntityType::class, [
                'class' => Configuration::class,
'choice_label' => 'id',
            ])
            ->add('utilisateur', EntityType::class, [
                'class' => Utilisateur::class,
'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Parametre::class,
        ]);
    }
}
