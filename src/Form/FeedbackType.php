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
use App\Entity\Feedback;

/**
 * FeedbackType
 *
 * Message form implementation.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class FeedbackType extends AbstractType
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
            ->add('subject', TextType::class, array(
                'constraints' => array(
                    new Assert\NotBlank(['groups' => ['default']])
                ),
                'validation_groups' => 'default'
            ))
            ->add('message', TextType::class, array(
                'constraints' => array(
                    new Assert\NotBlank(['groups' => ['default']])
                ),
                'validation_groups' => 'default'
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Feedback::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
            'validation_groups' => ['default']
        ));
    }
}
