<?php

/**
 * This file is part of the BaluProperty Package.
 * File manages the form actions of Message creation
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Message;
use App\Repository\MessageRepository;

/**
 * MessageType
 *
 * Message form implementation.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class MessageArchiveType extends AbstractType
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
            ->add('archive', ChoiceType::class, [
                'choices' => [true, false],
                'required' => true,
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank()
                ],
                'validation_groups' => ['default']
            ])
            ->add('messageId', EntityType::class, [
                'class' => Message::class,
                'query_builder' => function (MessageRepository $er) {
                    return $er->createQueryBuilder('u');
                },
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(['groups' => ['damageThread']])
                ]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Message::class,
            'csrf_protection' => false
        ));
    }
}
