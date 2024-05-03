<?php

namespace App\Form;

use App\Entity\UserIdentity;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationType extends AbstractType
{
    /**
     * User Registration form type.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, array(
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ))
            ->add('password', PasswordType::class, array(
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ))
            ->add('confirmPassword', PasswordType::class, array(
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ))
            ->add('firstName', TextType::class, array(
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ))
            ->add('lastName', TextType::class, array(
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ))
            ->add('dob', DateType::class, array(
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ))
            ->add('street', TextType::class, array(
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ))
//            ->add('state', TextType::class, array(
//                'mapped' => false,
//                'constraints' => [
//                    new Assert\NotBlank()
//                ]
//            ))
            ->add('streetNumber', TextType::class, array(
                'mapped' => false,
            ))
            ->add('city', TextType::class, array(
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ))
            ->add('zipCode', TextType::class, array(
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ))
            ->add('phone', TextType::class, array(
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ))
            ->add('country', TextType::class, array(
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ))
            ->add('countryCode', TextType::class, array(
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ))
            ->add('website', TextType::class)
            ->add('isPolicyAccepted')
            ->add('jobTitle')
            ->add('role', TextType::class, array(
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ))
            ->add('category', CollectionType::class, array(
                'entry_type' => TextType::class,
                'allow_add' => true,
                'mapped' => false
            ))
            ->add('landLine', TextType::class, array(
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