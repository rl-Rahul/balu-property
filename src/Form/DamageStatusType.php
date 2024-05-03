<?php

/*
 * This file is part of the BaluProperty Package.
 * File manages the form actions of Damage creation
 */

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Damage;
use App\Utils\Constants;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use App\Entity\UserIdentity;

/**
 * DamagStatusType
 *
 * DamagStatus form implementation.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class DamageStatusType extends AbstractType
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
            ->add('ticket', EntityType::class, [
                'class' => Damage::Class,
                'choice_label' => 'preferredCompany',
                'constraints' => [
                    new NotBlank(),
                ],
                'required' => true,
                'choice_value' => 'publicId',
                'mapped' => false
            ])
            ->add('status', TextType::class, [
                'constraints' => [
                    new NotBlank()
                ],
                'mapped' => false
            ])
            ->add('currentStatus', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'mapped' => false
            ])
            ->add('comment', TextType::class, [
                'mapped' => false])
            ->add('date', DateType::class, [
                'constraints' => [
                    new NotBlank(['groups' => ['scheduledDate']]),
                ],
                'input' => 'datetime',
                'widget' => 'single_text',
                'mapped' => false,
            ])
            ->add('time', TimeType::class, [
                'constraints' => [
                    new NotBlank(['groups' => ['scheduledDate']]),
                ],
                'input' => 'datetime',
                'widget' => 'single_text',
                'mapped' => false,
            ])
            ->add('withSignature', ChoiceType::class, [
                'choices' => Constants::BOOLEAN,
                'mapped' => false,
            ])
            ->add('signature', TextType::class, [
                'constraints' => [
                    new NotBlank(['groups' => ['repairConfirmedWithSignature']]),
                ],
                'mapped' => false,
            ])
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank(['groups' => ['defectRaised']]),
                ],
                'mapped' => false
            ])
            ->add('description', TextType::class, [
                'constraints' => [
                    new NotBlank(['groups' => ['defectRaised']]),
                ],
                'mapped' => false
            ])
            ->add('attachment', CollectionType::class, array(
                'entry_type' => TextType::class,
                'mapped' => false,
                'allow_add' => true,
            ))
            ->add('company', EntityType::class, array(
                'class' => UserIdentity::Class,
                'constraints' => array(
                    new NotBlank(['groups' => ['sendToCompany']]),
                ),
                'required' => false,
                'choice_value' => 'publicId',
                'empty_data' => null,
                'mapped' => false
            ))
            ->add('issueType', EntityType::class, [
                'class' => Category::Class,
                'choice_label' => 'name',
                'choice_value' => 'publicId',
            ])
            ->add('offer', TextType::class, [
                'mapped' => false
            ]);
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
