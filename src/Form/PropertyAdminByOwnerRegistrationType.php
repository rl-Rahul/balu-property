<?php

/**
 * This file is part of the BaluProperty Package.
 * File manages the form actions of new self registration
 */

namespace App\Form;

use App\Entity\UserIdentity;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * PropertyAdminByOwnerRegistrationType
 *
 * Change password form implementation.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class PropertyAdminByOwnerRegistrationType extends RegistrationType
{
    /**
     * User Registration form type.
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->remove("website")
            ->remove("street")
            ->remove("zipCode")
            ->remove("password")
            ->remove("confirmPassword")
            ->remove("dob")
            ->remove("streetNumber")
            ->remove("city")
            ->remove("zipCode")
            ->remove("isPolicyAccepted")
            ->remove("category")
            ->remove("country")
            ->remove("countryCode")
            ->remove("phone")
            ->remove("landLine")
            ->add("language", TextType::class)
            ->add("phone", TextType::class, array(
                'mapped' => false
            ))
            ->add("landLine", TextType::class, array(
                'mapped' => false
            ))
            ->add("sendInvite", RadioType::class, array(
                'mapped' => false,
            ))
            ->add('website', TextType::class, array(
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ))
            ->add('administratorName', TextType::class, array(
                'constraints' => [
                    new Assert\NotBlank()
                ]
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
