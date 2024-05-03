<?php

/**
 * This file is part of the BaluProperty Package.
 * File manages the form actions of new self registration
 */

namespace App\Form;

use App\Entity\UserIdentity;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\OwnerRegistrationType;

/**
 * TenantRegistrationType
 *
 * Tenant Registration form implementation.
 *
 * @package         BaluProperty
 * @subpackage      App
 * @author          pitsolutions.ch
 */
class TenantRegistrationType extends OwnerRegistrationType
{
    /**
     * Tenant Registration form type.
     *
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->remove("password")
            ->add('contractStartDate', DateType::class, array(
                'widget' => 'single_text',
                'input' => 'datetime',
                'required' => false,
                'mapped' => false
            ))
            ->add('contractEndDate', DateType::class, array(
                'widget' => 'single_text',
                'input' => 'datetime',
                'required' => false,
                'mapped' => false
            ))
            ->add('noticePeriodDays', TextType::class, array(
                'required' => false,
                'mapped' => false
            ))
            ->add('rent', TextType::class, array(
                'required' => false,
                'mapped' => false
            ))
            ->add('apartment', EntityType::class, array(
                'class' => 'AppBundle:BpApartment',
                'choice_label' => 'apartment',
                'required' => true,
                'mapped' => false
            ))
            ->add('fixedTermContract', TextType::class, array(
                'required' => false,
                'mapped' => false
            ))
            ->add('active', TextType::class, array(
                'required' => true,
                'mapped' => false
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserIdentity::class,
            'csrf_protection' => false,
        ]);
    }
}
