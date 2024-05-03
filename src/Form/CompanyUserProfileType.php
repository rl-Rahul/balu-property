<?php

/**
 * This file is part of the BaluProperty Package.
 * File manages the form actions of new self registration
 */

namespace App\Form;

use App\Entity\UserIdentity;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CompanyUserProfileType
 *
 * edit company profile form implementation.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class CompanyUserProfileType extends ProfileType
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
            ->add('companyName', TextType::class)
            ->add('language', TextType::class, array(
                'mapped' => false,
            ))
            ->remove("isPolicyAccepted")
            ->remove("website");
//        $builder
//            ->remove("street")
//            ->remove("zipCode")
//            ->remove("city")
//            ->add('companyName',TextType::class, array(
//                'constraints' => [
//                    new Assert\NotBlank()
//                ]
//            ))
//            ->add('latitude', TextType::class, array(
//                'mapped' => false,
//            ))
//            ->add('longitude', TextType::class, array(
//                'mapped' => false,
//            ))
//            ->add('street', TextType::class, array(
//                'mapped' => false,
//            ))
//            ->add('zipCode', TextType::class, array(
//                'mapped' => false,
//            ))
//            ->add('city', TextType::class, array(
//                'mapped' => false,
//            ))
//        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserIdentity::class,
            'csrf_protection' => false,
        ]);
    }
}
