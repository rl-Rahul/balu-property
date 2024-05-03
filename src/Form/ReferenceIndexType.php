<?php

namespace App\Form;

use App\Entity\ReferenceIndex;
use App\Utils\Constants;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ReferenceIndexType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, array(
                'required' => true,
            ))->add('sortOrder', IntegerType::class, array(
                'required' => false,
            ))->add('active', ChoiceType::class, array(
                'required' => false,
                'choices' => Constants::BOOLEAN,
                'mapped' => false,
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReferenceIndex::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ]);
    }
}
