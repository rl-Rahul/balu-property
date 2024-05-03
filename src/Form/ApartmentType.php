<?php

/**
 * This file is part of the BaluProperty Package.
 * File manages the form actions of new self registration
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Apartment;
use App\Entity\Floor;
use App\Entity\ReferenceIndex;
use App\Entity\LandIndex;
use App\Utils\Constants;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use App\Repository\FloorRepository;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;


/**
 * Form to manage object add and edit
 *
 * @author pitsolutions.ch
 */
class ApartmentType extends AbstractType
{

    /**
     * Property form type.
     *
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('floorNumber', TextType::class, array(
                'required' => true,
                'mapped' => false
            ))
            ->add('officialNumber', IntegerType::class, array(
                'required' => true,
                'mapped' => true,
            ))
            ->add('name', TextType::class, array(
                'required' => true,
                'mapped' => true
            ))
            ->add('sortOrder', IntegerType::class, array(
                'required' => true,
                'mapped' => true
            ))
            ->add('type', TextType::class, array(
                'required' => true,
                'mapped' => false
            ))
            ->add('baseIndexDate', DateType::class, array(
                'widget' => 'single_text',
                'input' => 'datetime',
                'required' => false,
                'mapped' => false
            ))
            ->add('roomCount', TextType::class, array(
                'required' => false,
                'mapped' => true
            ))
            ->add('volume', TextType::class, array(
                'required' => true,
                'mapped' => true
            ))
            ->add('ceilingHeight', TextType::class, array(
                'required' => true,
                'mapped' => true
            ))
            ->add('maxFloorLoading', TextType::class, array(
                'required' => true,
                'mapped' => true
            ))
            ->add('area', TextType::class, array(
                'required' => true,
                'mapped' => true
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Apartment::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ));
    }
}
