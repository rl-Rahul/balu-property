<?php

namespace App\Form;

use App\Entity\Directory;
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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Utils\Constants;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use App\Service\DirectoryService;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class DirectoryType extends AbstractType
{
    /**
     * @var DirectoryService $directoryService
     */
    private DirectoryService $directoryService;

    public function __construct(DirectoryService $directoryService)
    {
        $this->directoryService = $directoryService;
    }

    /**
     * Individual form type.
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
                    new Assert\Optional(),
                    new Assert\Email([
                        'message' => 'The email "{{ value }}" is not a valid email.',
                    ])
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
            ->add('phone', TextType::class)
            ->add('publicId', TextType::class, array(
                'mapped' => false
            ))
            ->add('sendInvite', ChoiceType::class, [
                'choices' => [
                    '1' => true,
                    '0' => false
                ],
                'mapped' => false,

            ])
            ->add('landLine', TextType::class, array())
            ->add('latitude', TextType::class, array(
                'mapped' => false,
            ))
            ->add('longitude', TextType::class, array(
                'mapped' => false,
            ))
            ->add('street', TextType::class, array())
            ->add('streetNumber', TextType::class, array())
            ->add('countryCode', TextType::class, array())
            ->add('country', TextType::class, array())
            ->add('zipCode', TextType::class, array())
            ->add('city', TextType::class, array())
            ->add('dob', DateType::class, array(
                'widget' => 'single_text',
//                'constraints' => [
//                    new Assert\NotBlank()
//                ]
            ))
            ->add('state', TextType::class, array())
            ->add('janitorInvite', ChoiceType::class, [
                'choices' => [
                    '1' => true,
                    '0' => false
                ],
                'mapped' => false,

            ])
            ->add('property', TextType::class, array(
                'mapped' => false,
            ))
            ->add('companyName')
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $formData = $event->getData();
                $form = $event->getForm();
                if (!isset($formData['email']) || empty($formData['email'])) {
                    $formData['email'] = $this->directoryService->getDynamicEmail($formData['firstName']);
                    $form->add('isSystemGeneratedEmail', HiddenType::class, [
                        'data' => true,
                        'empty_data' => true,
                        'mapped' => true,
                    ]);
                    $event->setData($formData);
                }
            })
            ->getForm();
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Directory::class,
            'csrf_protection' => false,
        ]);
    }
}