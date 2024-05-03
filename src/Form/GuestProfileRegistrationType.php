<?php

/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Form;

use App\Entity\Category;
use App\Entity\UserIdentity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\DateType;

/**
 * Description of GuestProfileRegistrationType
 *
 * @author pitsolutions.ch
 */
class GuestProfileRegistrationType extends RegistrationType
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
            ->remove("password")
            ->remove("confirmPassword")
            ->remove("dob")
            ->remove("sendInvite")
            ->remove("document")
            ->remove("website")
            ->remove("language")
            ->remove("role");
        $builder
            ->add('companyName', TextType::class, array(
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ))
            ->add('street', TextType::class, array(
                'mapped' => false,
            ))
            ->add('streetNumber', TextType::class, array(
                'mapped' => false,
            ))
            ->add('phone', TextType::class, array(
                'mapped' => false,
            ))
            ->add('zipCode', TextType::class, array(
                'mapped' => false,
            ))
            ->add('country', TextType::class, array(
                'mapped' => false,
            ))
            ->add('countryCode', TextType::class, array(
                'mapped' => false,
            ))
            ->add('city', TextType::class, array(
                'mapped' => false,
            ))
            ->add('latitude', TextType::class, array(
                'mapped' => false,
            ))
            ->add('longitude', TextType::class, array(
                'mapped' => false,
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
