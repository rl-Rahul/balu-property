<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PasswordType extends AbstractType
{
    /**
     * function build form
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('newPassword', null, ['required' => true])
            ->add('confirmPassword', null, ['required' => true]);
        if (isset($options['data']['current']) && (true === $options['data']['current'])) {
            $builder
                ->add('currentPassword', null, ['required' => true]);
        }
        if (isset($options['data']['reset']) && (true === $options['data']['reset'])) {
            $builder
                ->add('token', null, ['required' => true]);
        }
    }

    /**
     * function configureOption
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => false,
        ]);
    }
}