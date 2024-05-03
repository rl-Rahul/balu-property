<?php

/**
 * This file is part of the BaluProperty Package.
 * File manages the form actions of Damage creation
 */

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\UserIdentity;
use App\Entity\Apartment;
use App\Entity\Damage;

/**
 * DamageType
 *
 * Damage form implementation.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class DamageType extends AbstractType
{
    /**
     * Damage/Ticket create form type.
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('title', TextType::class, array(
                'required' => true,
                'constraints' => array(
                    new NotBlank(['groups' => ['default']]),
                )
            ))
            ->add('description', TextareaType::class, array(
                'constraints' => array(
                    new NotBlank(['groups' => ['default']]),
                )
            ))
            ->add('isDeviceAffected', ChoiceType::class, [
                'choices' => [
                    '1' => true,
                    '0' => false
                ],
                'mapped' => true,
            ])
            ->add('apartment', EntityType::class, array(
                'class' => Apartment::Class,
                'choice_label' => 'apartment',
                'choice_value' => 'publicId',
                'required' => true,
                'constraints' => array(
                    new NotBlank(['groups' => ['default']]),
                )
            ))
            ->add('damageImages', CollectionType::class, array(
                'entry_type' => TextType::class,
                'mapped' => false,
                'allow_add' => true,
            ))
            ->add('barCode', TextType::class, array(
                'required' => false
            ))
            ->add('isFloorPlanEdit', ChoiceType::class, [
                'choices' => [
                    '1' => true,
                    '0' => false
                ],
                'mapped' => false,
            ])
            ->add('allocation', ChoiceType::class, [
                'choices' => [
                    '1' => true,
                    '0' => false
                ],
                'empty_data' => '0',
                'mapped' => true,
            ])
            ->add('issueType', EntityType::class, [
                'class' => Category::Class,
                'choice_label' => 'name',
                'choice_value' => 'publicId',
            ])
            ->add('isJanitorEnabled');
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Damage::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
            'validation_groups' => ['default']
        ));
    }
}
