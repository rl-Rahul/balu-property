<?php

/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
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
 * Description of OwnerRegistrationType
 *
 * @author pitsolutions.ch
 */
class OwnerRegistrationType extends RegistrationType
{
    /**
     * Owner Registration form type.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
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
