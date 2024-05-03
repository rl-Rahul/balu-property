<?php

namespace App\Utils;

use App\Entity\Apartment;
use App\Entity\Property;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Context\ExecutionContext;
use App\Entity\Folder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Service\SecurityService;
use App\Entity\PropertyGroup;
use App\Entity\ObjectContracts;
use App\Entity\PushNotification;

/**
 * This utility class will provide validation constraints for validating
 * user provided data at different API end points.
 *
 * @author Vidya L V<vidya.l@pitsolutions.com>
 */
class ValidationConstraints
{
    /**
     * @var $oRequest
     */
    private $oRequest;

    /**
     * @var TranslatorInterface $oTranslator
     */
    private TranslatorInterface $oTranslator;

    /**
     * @var ManagerRegistry $doctrine
     */
    private ManagerRegistry $doctrine;

    /**
     * @var ParameterBagInterface $params
     */
    private ParameterBagInterface $params;

    /**
     * @var SecurityService $securityService
     */
    private SecurityService $securityService;

    /**
     * @param RequestStack $requestStack
     * @param ManagerRegistry $doctrine
     * @param ParameterBagInterface $params
     * @param TranslatorInterface $oTranslator
     * @param SecurityService $securityService
     */
    public function __construct(RequestStack $requestStack, ManagerRegistry $doctrine, ParameterBagInterface $params, TranslatorInterface $oTranslator,
                                SecurityService $securityService
    )
    {
        $this->oRequest = $requestStack->getCurrentRequest();
        $this->doctrine = $doctrine;
        $this->oTranslator = $oTranslator;
        $this->params = $params;
        $this->securityService = $securityService;
    }

    /**
     * Wrapper function to call and return intended constraints collection.
     *
     * @param string $sType
     *
     * @return Collection
     */
    public function get(string $sType): Collection
    {
        $sFunction = 'get' . ucfirst($sType) . 'Constraints';

        $aFields = $this->$sFunction();
        return new Collection([
            'fields' => $aFields,
            'extraFieldsMessage' => '{{ field }} is not expected',
            'missingFieldsMessage' => '{{ field }} is missing',
        ]);
    }


    /**
     * Return the constraints collection for notification status validation.
     *
     * @return array
     */
    public function getFolderConstraints(): array
    {
        return [
            'name' => [
                new Assert\NotBlank([
                    'message' => 'name ' . $this->oTranslator->trans('shouldNotBlank'),
                ]),
                new Assert\Type([
                    'type' => 'string',
                    'message' => $this->oTranslator->trans('shouldBeString'),
                ]),
                new Assert\Callback(function ($object, ExecutionContext $context) {
                    if (strpbrk($object, "\\/?%*:|\"<>") !== FALSE) {
                        $context->buildViolation($this->oTranslator->trans('invalidFolderName'))->addViolation();
                    }
                }),
            ],
            'isPrivate' => [
                new Assert\Type([
                    'type' => 'string',
                    'message' => 'isPrivate ' . $this->oTranslator->trans('shouldBeString'),
                ])
            ],
            'isManual' => [
                new Assert\Type([
                    'type' => 'bool',
                    'message' => 'isManual ' . $this->oTranslator->trans('shouldBeBool'),
                ])
            ],
            'parent' => [
                new Assert\Optional(
                    new Assert\Callback(function ($object, ExecutionContext $context) {
                        $em = $this->doctrine->getManager();
                        if (!$em->getRepository(Folder::class)->findOneBy(['publicId' => $object])) {
                            $context->buildViolation($this->oTranslator->trans('invalidParent'))
                                ->addViolation();
                        }
                    })

                )
            ]
        ];
    }

    /**
     * Return the constraints collection for upload data validation.
     *
     * @return array
     */
    public function getUploadConstraints(): array
    {
        return [
                'files[]' => [
                    new Assert\NotBlank(),
                    new Assert\All(
                        array(
                            'constraints' => array(
                                new Assert\Count(['min' => 1, 'max' => $this->params->get('max_upload_count')]),
                                new Assert\All(array(
                                    'constraints' => array(
                                        new Assert\File(array(
                                            'maxSize' => $this->params->get('file_size_max')
                                        )),
                                    ),
                                )),
                            )
                        )
                    )
                ],
            ] + $this->getCommonTempUploadConstraints();
    }

    /**
     * Return the constraints collection for upload data validation.
     *
     * @return array
     */
    public function getDocumentUploadConstraints(): array
    {
        return [
                'files[]' => [
                    new Assert\NotBlank(),
                    new Assert\All(
                        array(
                            'constraints' => array(
                                new Assert\Count(['min' => 1, 'max' => $this->params->get('max_upload_count')]),
                                new Assert\All(array(
                                    'constraints' => array(
                                        new Assert\File(array(
                                            'maxSize' => $this->params->get('file_size_max')
                                        )),
                                    ),
                                )),
                            )
                        )
                    )
                ]
            ] + $this->getDocumentUploadCommonConstraints();
    }

    /**
     * Return the constraints collection for group validation.
     *
     * @return array
     */
    public function getGroupConstraints(): array
    {
        return [
            'name' => [
                new Assert\Type([
                    'type' => 'string',
                    'message' => $this->oTranslator->trans('shouldBeString'),
                ]),
                new Assert\NotBlank([
                    'message' => $this->oTranslator->trans('shouldNotBlank'),
                ]),
                new Assert\Callback(function ($object, ExecutionContext $context) {
                    $em = $this->doctrine->getManager();
                    $groupUuid = $this->oRequest->get('uuid', null);
                    $group = $em->getRepository(PropertyGroup::class)->getGroupName($this->securityService->getUser());
                    if (is_null($groupUuid)) {
                        // New Group
                        if (in_array($object, $group)) {
                            $context->buildViolation($this->oTranslator->trans('groupAlreadyExists'))->addViolation();
                        }
                    } else {
                        // Edit Group
                        $updatingGroup = $em->getRepository(PropertyGroup::class)->findOneBy(['publicId' => $groupUuid, 'deleted' => 0]);
                        if ($updatingGroup instanceof PropertyGroup && in_array($object, $group)) {
                            $context->buildViolation($this->oTranslator->trans('groupAlreadyExists'))->addViolation();
                        }
                    }
                }),
            ]
        ];
    }

    /**
     * Return the constraints collection for notification status validation.
     *
     * @return \Symfony\Component\Validator\Constraints\Collection
     */
    public function getNotificationStatusConstraints(): array
    {
        return [
            'notificationId' => [
                new Assert\NotBlank([
                    'message' => 'notificationId ' . $this->oTranslator->trans('shouldNotBlank'),
                ]),
                new Assert\Type([
                    'type' => 'array',
                    'message' => $this->oTranslator->trans('typeError'),
                ])
            ],
            'isRead' => [
                new Assert\Type([
                    'type' => 'bool',
                    'message' => 'isRead ' . $this->oTranslator->trans('typeError'),
                ])
            ],
        ];
    }

    /**
     * Return the constraints collection for upload data validation.
     *
     * @return array
     */
    public function getCameraUploadConstraints(): array
    {
        return [
                'fileData' => [
                    new Assert\NotBlank(),
                ],
                'fileName' => [
                    new Assert\NotBlank(),
                ],

            ] + $this->getCommonTempUploadConstraints();
    }

    /**
     * Return the constraints collection for upload data validation.
     *
     * @return array
     */
    public function getCameraDocumentUploadConstraints(): array
    {
        return [
                'fileData' => [
                    new Assert\NotBlank(),
                ],
            ] + $this->getDocumentUploadCommonConstraints();
    }

    /**
     *
     * @return array
     */
    private function getDocumentUploadCommonConstraints(): array
    {
        return [
            'property' => [
                new Assert\Optional(
                    new Assert\Callback(function ($object, ExecutionContext $context) {
                        $em = $this->doctrine->getManager();
                        if (!$em->getRepository(Property::class)->findOneBy(['publicId' => $object])) {
                            $context->buildViolation($this->oTranslator->trans('invalidProperty'))
                                ->addViolation();
                        }
                    })

                )
            ],
            'apartment' => [
                new Assert\Optional(
                    new Assert\Callback(function ($object, ExecutionContext $context) {
                        $em = $this->doctrine->getManager();
                        if (!$em->getRepository(Apartment::class)->findOneBy(['publicId' => $object])) {
                            $context->buildViolation($this->oTranslator->trans('invalidApartment'))
                                ->addViolation();
                        }
                    })

                )
            ],
            'contract' => [
                new Assert\Optional(
                    new Assert\Callback(function ($object, ExecutionContext $context) {
                        $em = $this->doctrine->getManager();
                        if (!$em->getRepository(ObjectContracts::class)->findOneBy(['publicId' => $object])) {
                            $context->buildViolation($this->oTranslator->trans('invalidContract'))
                                ->addViolation();
                        }
                    })

                )
            ],
            'type' => [
//                new Assert\NotBlank([
//                    'message' => 'type ' . $this->oTranslator->trans('shouldNotBlank'),
//                ]),
                new Assert\Optional(
//                    new Assert\Type([
//                        'type' => 'string',
//                        'message' => 'type ' . $this->oTranslator->trans('shouldBeString'),
//                    ]),
                    new Assert\Callback(function ($object, ExecutionContext $context) {
                        $entityType = ['property', 'apartment', 'contract', 'floorPlan', 'ticket', 'coverImage'];
                        if (!empty($object) && !in_array($object, $entityType)) {
                            $context->buildViolation($this->oTranslator->trans('invalidObjectType'))
                                ->addViolation();
                        }
                    }),
                )
            ],
            'subType' => [
                new Assert\Optional(
                    new Assert\Callback(function ($object, ExecutionContext $context) {
                        $entityType = $this->params->get('image_category');
                        if (!isset($entityType[$object])) {
                            $context->buildViolation($this->oTranslator->trans('invalidObjectType'))
                                ->addViolation();
                        }
                    }),
                )
            ],
            'folder' => [
                new Assert\NotBlank(),
                new Assert\Optional(
                    new Assert\Callback(function ($object, ExecutionContext $context) {
                        $em = $this->doctrine->getManager();
                        if (!$em->getRepository(Folder::class)->findOneBy(['publicId' => $object]) instanceof Folder) {
                            $context->buildViolation($this->oTranslator->trans('invalidFolder'))
                                ->addViolation();
                        }
                    })

                )
            ],
            'permission' => [
                new Assert\NotBlank([
                    'message' => 'permission ' . $this->oTranslator->trans('shouldNotBlank'),
                ]),
                new Assert\Type([
                    'type' => 'string',
                    'message' => $this->oTranslator->trans('shouldBeString'),
                ]),
                new Assert\Callback(function ($object, ExecutionContext $context) {
                    $entityType = ['private', 'public'];
                    if (!in_array($object, $entityType)) {
                        $context->buildViolation($this->oTranslator->trans('invalidPermissionType'))
                            ->addViolation();
                    }
                }),
            ],
            'fileName' => [
                new Assert\Type([
                    'type' => 'string',
                    'message' => $this->oTranslator->trans('shouldBeString'),
                ]),
                new Assert\Callback(function ($object, ExecutionContext $context) {
                    if (strpbrk($object, "\\/?%*:|\"<>") !== FALSE) {
                        $context->buildViolation($this->oTranslator->trans('invalidFileName'))->addViolation();
                    }
                }),
            ],
            'isEncode' => [
                new Assert\Optional(
                    new Assert\Type([
                        'type' => 'string',
                        'message' => 'isEncode ' . $this->oTranslator->trans('shouldBeBool'),
                    ])
                )
            ],
            'pdfToJpg' => [
                new Assert\Optional(
                    new Assert\Type([
                        'type' => 'string',
                        'message' => 'isEncode ' . $this->oTranslator->trans('shouldBeString'),
                    ])
                )
            ],
            'page' => [
                new Assert\Optional(
                    new Assert\Type([
                        'type' => 'string',
                        'message' => 'isEncode ' . $this->oTranslator->trans('shouldBeString'),
                    ])
                )
            ]
        ];
    }

    /**
     *
     * @return array
     */
    private function getCommonTempUploadConstraints(): array
    {
        return [
            'type' => [
                new Assert\NotBlank([
                    'message' => 'objectType ' . $this->oTranslator->trans('shouldNotBlank'),
                ]),
                new Assert\Type([
                    'type' => 'string',
                    'message' => 'objectType ' . $this->oTranslator->trans('shouldBeString'),
                ]),
                new Assert\Callback(function ($object, ExecutionContext $context) {
                    if (!in_array($object, Constants::DOC_TYPES)) {
                        $context->buildViolation($this->oTranslator->trans('invalidObjectType'))
                            ->addViolation();
                    }
                }),
            ],
            'folder' => [
                new Assert\Optional(
                    new Assert\Callback(function ($object, ExecutionContext $context) {
                        $em = $this->doctrine->getManager();
                        if (!$em->getRepository(Folder::class)->findOneBy(['publicId' => $object]) instanceof Folder) {
                            $context->buildViolation($this->oTranslator->trans('invalidFolder'))
                                ->addViolation();
                        }
                    })

                )
            ],
            'isEncode' => [
                new Assert\Optional(
                    new Assert\Type([
                        'type' => 'string',
                        'message' => 'isEncode ' . $this->oTranslator->trans('shouldBeBool'),
                    ])
                )
            ],
            'fileName' => [
                new Assert\Optional(
                    new Assert\Type([
                        'type' => 'string',
                        'message' => 'isEncode ' . $this->oTranslator->trans('shouldBeString'),
                    ])
                )
            ],
            'pdfToJpg' => [
                new Assert\Optional(
                    new Assert\Type([
                        'type' => 'string',
                        'message' => 'isEncode ' . $this->oTranslator->trans('shouldBeString'),
                    ])
                )
            ],
            'page' => [
                new Assert\Optional(
                    new Assert\Type([
                        'type' => 'string',
                        'message' => 'isEncode ' . $this->oTranslator->trans('shouldBeString'),
                    ])
                )
            ]
        ];
    }
}
