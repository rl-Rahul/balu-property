<?php

/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Entity\Property;

/**
 * Form to manage property add and edit
 *
 * @author pitsolutions.ch
 */
class PropertyType extends AbstractType
{

    /**
     * Property form type.
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('address', TextType::class, array(
                'required' => true
            ))
            ->add('streetName', TextType::class, array(
                'required' => true
            ))
            ->add('streetNumber', TextType::class)
            ->add('postalCode', TextType::class)
            ->add('city', TextType::class)
            ->add('country', TextType::class, array(
                'required' => true
            ))
            ->add('countryCode', TextType::class, array(
                'required' => true
            ))
            ->add('latitude', TextType::class)
            ->add('longitude', TextType::class)
            ->add('administrator', TextType::class, array(
                'mapped' => false
            ))
            ->add('janitor', TextType::class, array(
                'mapped' => false
            ))
            ->add('propertyGroup', TextType::class, array(
                'mapped' => false
            ))
            ->add('owner', TextType::class, array(
                'mapped' => false
            ))
            ->add('documents', CollectionType::class, array(
                'entry_type' => TextType::class,
                'mapped' => false,
                'allow_add' => true,
            ))
            ->add('coverImage', CollectionType::class, array(
                'entry_type' => TextType::class,
                'mapped' => false,
                'allow_add' => true,
            ))
            ->add('currency', TextType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Property::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ));
    }
}
