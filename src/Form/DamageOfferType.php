<?php

namespace App\Form;

use App\Entity\DamageOffer;
use App\Entity\DamageRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Damage;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class DamageOfferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ticket', EntityType::class, array(
                'class' => Damage::class,
                'required' => true,
                'mapped' => false,
                'choice_value' => 'publicId',
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add('description')
            ->add('amount')
            ->add('attachment', CollectionType::class, array(
                'entry_type' => TextType::class,
                'mapped' => false,
                'allow_add' => true,
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DamageOffer::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ]);
    }
}
