<?php

/**
 * This file is part of the BaluProperty Package.
 * File manages the form actions of new self registration
 */

namespace App\Form;

use App\Entity\UserIdentity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ChangePasswordType
 *
 * Change password form implementation.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class UserProfileType extends OwnerProfileType
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
            ->remove("language")
            ->remove("role");
        $builder
            ->add('countryCode', TextType::class, array(
                'mapped' => false,
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
