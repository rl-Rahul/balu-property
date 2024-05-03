<?php

namespace App\Form;

use App\Entity\CompanyRating;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Damage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\Range;

class CompanyRatingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rating', NumberType::class, ['constraints' => [new Range([
                'min' => 1,
                'max' => 5
            ])]])
            ->add('ticket', EntityType::class, [
                'class' => Damage::Class,
                'choice_label' => 'preferredCompany',
                'constraints' => [
                    new NotBlank(),
                ],
                'required' => true,
                'choice_value' => 'publicId',
                'mapped' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CompanyRating::class,
            'csrf_protection' => false,
        ]);
    }
}
