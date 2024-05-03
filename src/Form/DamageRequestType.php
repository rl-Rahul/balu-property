<?php

namespace App\Form;

use App\Entity\DamageRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Damage;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class DamageRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('damage', EntityType::class, array(
                'class' => Damage::class,
                'required' => true,
                'choice_value' => 'publicId',
                'mapped' => false,
                'constraints' => array(
                    new NotBlank()
                )
            ))->add('company', CollectionType::class, array(
                'entry_type' => TextType::class,
                'allow_add' => true,
                'mapped' => false
            ))->add('requestedDate', DateType::class, array(
                'widget' => 'single_text',
                'input' => 'datetime',
                'required' => false,
                'mapped' => true
            ))->add('newOfferRequestedDate', DateType::class, array(
                'widget' => 'single_text',
                'input' => 'datetime',
                'required' => false,
                'mapped' => true
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DamageRequest::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ]);
    }
}
