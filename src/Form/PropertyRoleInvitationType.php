<?php

namespace App\Form;

use App\Entity\DamageRequest;
use App\Entity\Property;
use App\Entity\PropertyRoleInvitation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class PropertyRoleInvitationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('property', EntityType::class, array(
                'class' => Property::class,
                'required' => true,
                'choice_value' => 'publicId',
                'mapped' => false,
                'constraints' => array(
                    new NotBlank()
                )
            ))->add('role', TextType::class, array(
                'required' => true,
                'mapped' => false
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PropertyRoleInvitation::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ]);
    }
}
