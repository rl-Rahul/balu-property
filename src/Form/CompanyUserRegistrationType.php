<?php

/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Form;

use App\Entity\UserIdentity;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;

/**
 * Registration of company user
 *
 * @author pitsolutions.ch
 */
class CompanyUserRegistrationType extends RegistrationType
{

    /**
     * Company User Registration form type.
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('permission', CollectionType::class, array(
                'mapped' => false,
                'entry_type' => TextType::class,
                'mapped' => false,
                'allow_add' => true,
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ))
            ->add('jobTitle', TextType::class, array())
            ->add('companyName', TextType::class, array(
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ))
            ->add('latitude', TextType::class, array(
                'mapped' => false,
            ))
            ->add('longitude', TextType::class, array(
                'mapped' => false,
            ))
            ->add('street', TextType::class, array(
                'mapped' => false,
            ))
            ->add('streetNumber', TextType::class, array(
                'mapped' => false,
            ))
            ->add('countryCode', TextType::class, array(
                'mapped' => false,
            ))
            ->add('country', TextType::class, array(
                'mapped' => false,
            ))
            ->add('zipCode', TextType::class, array(
                'mapped' => false,
            ))
            ->add('city', TextType::class, array(
                'mapped' => false,
            ))
            ->add('phone', TextType::class, array(
                'mapped' => false,
            ))
            ->add('landLine', TextType::class, array(
                'mapped' => false,
            ))
            ->add('sendInvite', RadioType::class, array(
                'mapped' => false,
            ))
            ->add('dob', DateType::class, array(
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ))
            ->add('language', TextType::class)
            ->remove('password')
            ->remove('confirmPassword');
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserIdentity::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ]);
    }
}
