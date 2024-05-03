<?php

/**
 * This file is part of the BaluProperty Package.
 * File manages the form actions of new self registration
 */

namespace App\Form;

use App\Entity\ObjectContracts;
use App\Entity\UserIdentity;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Entity\RentalTypes;
use Doctrine\Persistence\ManagerRegistry;
use App\Utils\Constants;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ObjectContractType
 *
 * Change password form implementation.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class ObjectContractType extends AbstractType
{
    /**
     * @var TranslatorInterface $oTranslator
     */
    private TranslatorInterface $oTranslator;

    /**
     * @var ManagerRegistry $doctrine
     */
    private ManagerRegistry $doctrine;

    /**
     *
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine, TranslatorInterface $oTranslator)
    {
        $this->doctrine = $doctrine;
        $this->oTranslator = $oTranslator;
    }

    /**
     * Tenant Registration form type.
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('startDate', DateType::class, array(
                'widget' => 'single_text',
                'input' => 'datetime',
                'required' => false,
                'mapped' => true
            ))
            ->add('endDate', DateType::class, array(
                'widget' => 'single_text',
                'input' => 'datetime',
                'required' => false,
                'mapped' => true,
                'constraints' => array(
//                    new NotBlank(),
                    new Callback(array($this, 'validateEndDate')),
                ),
            ))
            ->add('ownerVote', ChoiceType::class, [
                'choices' => [
                    true,
                    false
                ],
                'mapped' => true
            ])
            ->add('contractPeriodType', TextType::class, array(
                'required' => false,
                'mapped' => false
            ))
            ->add('additionalComment', TextType::class, array(
                'required' => false,
                'mapped' => true
            ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ObjectContracts::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ));
    }

    /**
     *
     * @param type $value
     * @param ExecutionContextInterface $context
     */
    public function validateEndDate($value, ExecutionContextInterface $context)
    {
        $form = $context->getRoot();
        $data = $form->getData();
        // if fixed term, the end date is mandatory and should be valid
        $rentalType = $this->doctrine->getRepository(RentalTypes::class)->findOneBy(['publicId' => $form->get('contractPeriodType')->getData(), 'deleted' => 0]);
        if ($rentalType instanceof RentalTypes) {
            if ($rentalType->getType() === Constants::RENTAL_TYPE_FIXED && null === $data->getEndDate()) {
                $context->buildViolation($this->oTranslator->trans('endDateEmtpy'))->addViolation();
            }
            if ($rentalType->getType() === Constants::RENTAL_TYPE_FIXED && $value && ($data->getStartDate() >= $value)) {
                $context->buildViolation($this->oTranslator->trans('dateError'))->addViolation();
            }
            $curDate = new \DateTime("now");
            if ($value && $value->format('Y-m-d') < $curDate->format('Y-m-d')) {
                $context->buildViolation($this->oTranslator->trans('endDateCannotBePast'))->addViolation();
            }
            if ($rentalType->getType() === Constants::RENTAL_TYPE_OPENEND && null === $data->getEndDate()) {
                $formData['endDate'] = '';
            }
        }
    }
}
