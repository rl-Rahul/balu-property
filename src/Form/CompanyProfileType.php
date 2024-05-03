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
 * CompanyProfileType
 *
 * edit company profile form implementation.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class CompanyProfileType extends ProfileType
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
            ->add('companyName', TextType::class
            )
            ->add('language', TextType::class, array(
                'mapped' => false,
            ))
            ->add('document', TextType::class, array(
                'mapped' => false
            ))
            ->add('damage', TextType::class, array(
                'mapped' => false
            ));
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
