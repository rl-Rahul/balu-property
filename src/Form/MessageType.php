<?php

/**
 * This file is part of the BaluProperty Package.
 * File manages the form actions of Message creation
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Apartment;
use App\Repository\ApartmentRepository;
use App\Entity\Damage;
use App\Entity\Message;
use App\Utils\Constants;
use App\Repository\DamageRepository;

/**
 * MessageType
 *
 * Message form implementation.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class MessageType extends AbstractType
{
    /**
     * Message create form type.
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => Constants::MESSAGE_TYPE,
                'required' => true,
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(['groups' => ['default']])
                ],
                'validation_groups' => ['default']
            ])
            ->add('subject', TextType::class, array(
                'constraints' => array(
                    new Assert\NotBlank(['groups' => ['default']])
                ),
                'validation_groups' => 'default'
            ))
            ->add('message', TextType::class, array(
                'constraints' => array(
                    new Assert\Optional(),
                ),
                'validation_groups' => 'default'
            ))
            ->add('ticket', EntityType::class, [
                'class' => Damage::class,
                'query_builder' => function (DamageRepository $er) {
                    return $er->createQueryBuilder('u');
                },
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(['groups' => ['damageThread']])
                ]
            ])
            ->add('apartment', EntityType::class, [
                'class' => Apartment::class,
                'query_builder' => function (ApartmentRepository $er) {
                    return $er->createQueryBuilder('u');
                },
                'multiple' => true,
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['groups' => ['apartment']]),
                    new Assert\Count(
                        [
                            'groups' => ['apartment'],
                            'min' => 1
                        ]
                    )
                ]
            ])
            ->add('document', CollectionType::class, array(
                'entry_type' => TextType::class,
                'mapped' => false
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Message::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
            'validation_groups' => ['default']
        ));
    }
}
