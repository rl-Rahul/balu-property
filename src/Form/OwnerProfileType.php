<?php

/**
 * This file is part of the Balu Property Package.
 * File manages the form actions of new self registration
 */

namespace App\Form;

use App\Entity\UserIdentity;
use App\EventListener\DateObjectEventListener;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * OwnerProfileType
 *
 * Owner profile form implementation.
 *
 * @package         BaluProperty
 * @subpackage      App
 * @author          pitsolutions.ch
 */
class OwnerProfileType extends ProfileType
{
    /**
     * User Profile form type.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->remove("confirmPassword")
            ->remove("password")
            ->add('companyName', TextType::class)
            ->add('latitude', TextType::class, array(
                'mapped' => false,
            ))
            ->add('longitude', TextType::class, array(
                'mapped' => false,
            ))
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $formEvent = $event->getData();
                $formObj = $event->getForm();
                $formObj->get('dob')->setData(new \DateTime($formEvent['dob']));
            });
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
