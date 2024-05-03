<?php


namespace App\Service;
ini_set('memory_limit', '1024M');

use App\Entity\SubscriptionPlan;
use App\Entity\User;
use App\Entity\UserIdentity;
use App\Entity\LandIndex;
use App\Entity\Property;
use App\Entity\Apartment;
use App\Entity\ReferenceIndex;
use App\Entity\ContractTypes;
use App\Entity\ObjectContractDetail;
use App\Entity\ObjectAmenityMeasure;
use App\Entity\Amenity;
use App\Entity\ObjectTypes;
use App\Entity\Floor;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use App\Utils\ContainerUtility;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Utils\GeneralUtility;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use App\Utils\Constants;
use App\Entity\PropertyUser;
use App\Entity\ObjectContracts;
use App\Entity\Currency;
use App\Entity\ModeOfPayment;
use App\Entity\Document;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\ApartmentLog;
use App\Entity\ApartmentRentHistory;
use App\Entity\Role;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Entity\Damage;
use App\Entity\Folder;
use App\Entity\ResetObject;

/**
 * ObjectService
 *
 * Object service actions.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class ObjectService extends BaseService
{
    /**
     * @var ManagerRegistry $doctrine
     */
    private ManagerRegistry $doctrine;

    /**
     * @var ContainerUtility $containerUtility
     */
    private ContainerUtility $containerUtility;

    /**
     * @var GeneralUtility $generalUtility
     */
    private GeneralUtility $generalUtility;

    /**
     * @var ParameterBagInterface $params
     */
    private ParameterBagInterface $params;

    /**
     * @var PropertyService $propertyService
     */
    private PropertyService $propertyService;

    /**
     * @var DMSService $dmsService
     */
    private DMSService $dmsService;

    /**
     * @var UserService $userService ;
     */
    private UserService $userService;

    /**
     * @var TranslatorInterface $translator
     */
    private TranslatorInterface $translator;

    /**
     * @var $stripe
     */
    private $stripe;

    /**
     * UserService constructor.
     * @param ManagerRegistry $doctrine
     * @param ContainerUtility $containerUtility
     * @param ParameterBagInterface $params
     * @param GeneralUtility $generalUtility
     * @param PropertyService $propertyService
     * @param DMSService $dmsService
     * @param UserService $userService
     * @param TranslatorInterface $translator
     */
    public function __construct(ManagerRegistry $doctrine, ContainerUtility $containerUtility, ParameterBagInterface $params, GeneralUtility $generalUtility,
                                PropertyService $propertyService, DMSService $dmsService, UserService $userService, TranslatorInterface $translator)
    {
        $this->doctrine = $doctrine;
        $this->containerUtility = $containerUtility;
        $this->params = $params;
        $this->generalUtility = $generalUtility;
        $this->propertyService = $propertyService;
        $this->dmsService = $dmsService;
        $this->userService = $userService;
        $this->translator = $translator;
        $this->stripe = new \Stripe\StripeClient($params->get('stripe_secret'));
    }

    /**
     * Save object info
     * @param Property $property
     * @param Apartment $apartment
     * @param Request $request
     * @param UserIdentity $user
     * @param Form|null $form
     * @param bool|null $edit
     * @return Apartment
     * @throws \Exception
     */
    public function saveObjectInfo(Property $property, Apartment $apartment, Request $request, UserIdentity $user, ?Form $form = null, ?bool $edit = false): Apartment
    {
        $em = $this->doctrine->getManager();
        $objectType = $em->getRepository(ObjectTypes::class)->findOneBy([
            'publicId' => $request->get('objectType'), 'deleted' => 0]);
        if ($objectType instanceof ObjectTypes) {
            $apartment->setObjectType($objectType);
        }
        $floor = $em->getRepository(Floor::class)->findOneBy([
            'publicId' => $request->get('floorNumber'), 'deleted' => 0]);
        if ($floor instanceof Floor) {
            $apartment->setFloor($floor);
        }
        $this->preventEditIfActiveContract($apartment, $request);
        $apartment->setCreatedBy($user);
        $em->persist($apartment);
        $folder = null;
        $folderDisplayName = $this->propertyService->getFolderName($user, $request->request->get('name'), 'apartment');
        if (!$edit) {
            $parent = $property->getFolder()->getPublicId();
            $folderInfo = $this->dmsService->createFolder($folderDisplayName['displayName'], $user, true, $parent);
            if (!empty($folderInfo)) {
                $folder = $em->getRepository(Folder::class)->findOneBy(['identifier' => array_column($folderInfo, 'identifier')[0]]);
                $folder->setDisplayNameOffset($folderDisplayName['displayNameOffset']);
            }
        } else {
            $folder = $apartment->getFolder();
            $folder->setDisplayName($folderDisplayName['displayName']);
        }
        $apartment->setFolder($folder);
        $contractType = $em->getRepository(ContractTypes::class)->findOneBy(['publicId' => $request->get('contractType'), 'deleted' => 0]);
        if ($contractType instanceof ContractTypes) {
            if ($contractType->getType() === Constants::OBJECT_CONTRACT_TYPE_RENTAL) {
                $this->saveRentalContractInfo($apartment, $request, $contractType, $form);
            } else if ($contractType->getType() === Constants::OBJECT_CONTRACT_TYPE_OWNER) {
                $this->saveOwnershipContractInfo($apartment, $request, $contractType);
//                $objectOwnerRole = $em->getRepository(Role::class)->findOneBy(['roleKey' => $this->params->get('user_roles')['object_owner']]);
//                $user->addRole($objectOwnerRole);
            }
        }
        $this->saveObjectAmenityInfo($apartment, $request);
        if (!empty($request->request->get('documents'))) {
            $this->dmsService->persistDocument($request->request->get('documents'), $apartment, 'documents');
        }

        if (!empty($request->request->get('floorPlan'))) {
            $this->dmsService->persistDocument($request->request->get('floorPlan'), $apartment, 'floorPlan');
        }
        if (!empty($request->request->get('coverImage'))) {
            $this->dmsService->deleteCoverImageIfExists($apartment);
            $this->dmsService->persistDocument($request->request->get('coverImage'), $apartment, 'coverImage');
        }

        return $apartment;
    }

    /**
     *
     * @param Apartment $apartment
     * @param Request $request
     * @param ContractTypes $contractType
     * @param Form $form
     * @return void
     * @throws \Exception
     */
    private function saveRentalContractInfo(Apartment $apartment, Request $request, ContractTypes $contractType, Form $form): void
    {
        $em = $this->doctrine->getManager();
        $modeOfPayment = null;
        $additionalCostCurrency = null;
        $netRentRateCurrency = null;
        $objectDetail = $em->getRepository(ObjectContractDetail::class)->findOneBy(['object' => $apartment, 'deleted' => false]);
        if (null === $objectDetail) {
            $objectDetail = new ObjectContractDetail();
            $objectDetail->setObject($apartment);
        }
        $landIndex = $referenceIndex = null;
        if (null !== $request->get('landIndex')) {
            $landIndex = $em->getRepository(LandIndex::class)->findOneBy(['publicId' => $request->get('landIndex'), 'deleted' => 0]);
        }
        if (null !== $request->get('referenceRate')) {
            $referenceIndex = $em->getRepository(ReferenceIndex::class)->findOneBy(['publicId' => $request->get('referenceRate'), 'deleted' => 0]);
        }
        if (null !== $request->get('netRentRateCurrency')) {
            $netRentRateCurrency = $em->getRepository(Currency::class)->findOneBy(['publicId' => $request->get('netRentRateCurrency'), 'deleted' => 0]);
        }
        if (null !== $request->get('additionalCostCurrency')) {
            $additionalCostCurrency = $em->getRepository(Currency::class)->findOneBy(['publicId' => $request->get('additionalCostCurrency'), 'deleted' => 0]);
        }
        if (null !== $request->get('modeOfPayment')) {
            $modeOfPayment = $em->getRepository(ModeOfPayment::class)->findOneBy(['publicId' => $request->get('modeOfPayment'), 'deleted' => 0]);
        }
        //$date = ( !empty($request->get('baseIndexDate'))) ? \DateTime::createFromFormat('Y-m-d\TH:i:s.\0\0\0\Z', $request->get('baseIndexDate')) : null;
        $requestKeys = ['baseIndexDate' => $form->get('baseIndexDate')->getData(), 'modeOfPayment' => $modeOfPayment,
            'contractType' => $contractType, 'baseIndexValue' => empty($request->get('baseIndexValue')) ? null : $request->get('baseIndexValue'),
            'additionalCost' => empty($request->get('additionalCost')) ? null : $request->get('additionalCost'),
            'additionalCostCurrency' => $additionalCostCurrency,
            'netRentRate' => empty($request->get('netRentRate')) ? null : $request->get('netRentRate'),
            'netRentRateCurrency' => $netRentRateCurrency, 'landIndex' => $landIndex,
            'referenceRate' => $referenceIndex];
        $this->containerUtility->convertRequestKeysToSetters($requestKeys, $objectDetail);
    }

    /**
     *
     * @param Apartment $apartment
     * @param Request $request
     * @param ContractTypes $contractType
     * @return void
     * @throws \Exception
     */
    private function saveOwnershipContractInfo(Apartment $apartment, Request $request, ContractTypes $contractType): void
    {
        $em = $this->doctrine->getManager();
        //delete if already have object details
        $modeOfPayment = null;
        $additionalCostCurrency = null;
        $netRentRateCurrency = null;
        $objectDetail = $em->getRepository(ObjectContractDetail::class)->findOneBy(['object' => $apartment, 'deleted' => false]);
        if (null === $objectDetail) {
            $objectDetail = new ObjectContractDetail();
            $objectDetail->setObject($apartment);
        }

//        $em->persist($objectDetail);
//        $em->flush();
        if (null !== $request->get('additionalCostCurrency')) {
            $additionalCostCurrency = $em->getRepository(Currency::class)->findOneBy(['publicId' => $request->get('additionalCostCurrency'), 'deleted' => 0]);
        }
        if (null !== $request->get('modeOfPayment')) {
            $modeOfPayment = $em->getRepository(ModeOfPayment::class)->findOneBy(['publicId' => $request->get('modeOfPayment'), 'deleted' => 0]);
        }
        $requestKeys = [
            'contractType' => $contractType,
            'totalObjectValue' => empty($request->get('totalObjectValue')) ? null : $request->get('totalObjectValue'),
            'additionalCostBuilding' => empty($request->get('additionalCostBuilding')) ? null : $request->get('additionalCostBuilding'),
            'additionalCostHeating' => empty($request->get('additionalCostHeating')) ? null : $request->get('additionalCostHeating'),
            'additionalCostElevator' => empty($request->get('additionalCostElevator')) ? null : $request->get('additionalCostElevator'),
            'additionalCostParking' => empty($request->get('additionalCostParking')) ? null : $request->get('additionalCostParking'),
            'additionalCostRenewal' => empty($request->get('additionalCostRenewal')) ? null : $request->get('additionalCostRenewal'),
            'additionalCostMaintenance' => empty($request->get('additionalCostMaintenance')) ? null : $request->get('additionalCostMaintenance'),
            'additionalCostAdministration' => empty($request->get('additionalCostAdministration')) ? null : $request->get('additionalCostAdministration'),
            'additionalCost' => empty($request->get('additionalCost')) ? null : $request->get('additionalCost'),
            'additionalCostEnvironment' => empty($request->get('additionalCostEnvironment')) ? null : $request->get('additionalCostEnvironment'),
            'modeOfPayment' => $modeOfPayment,
            'additionalCostCurrency' => $additionalCostCurrency];
        $this->containerUtility->convertRequestKeysToSetters($requestKeys, $objectDetail);
    }


    /**
     * Saves object types info
     * @param Apartment $apartment
     * @param Request $request
     * @return void
     * @throws \Exception
     */
    private function saveObjectAmenityInfo(Apartment $apartment, Request $request): void
    {
        $em = $this->doctrine->getManager();
        if (null !== $request->get('amenity')) {
            //delete if already have object types
            $em->getRepository(ObjectAmenityMeasure::class)->removeAmenityMeasures($apartment);
            if (!empty($request->get('amenity')) && is_array($request->get('amenity'))) {
                foreach ($request->get('amenity') as $type) {
                    $value = isset($type['value']) ? $type['value'] : 0;
                    if ($amenity = $em->getRepository(Amenity::class)->findOneBy(['publicId' => $type['id'], 'deleted' => 0, 'active' => 1])) {
                        $amenityMeasure = new ObjectAmenityMeasure();
                        $requestKeys = ['value' => $value, 'object' => $apartment, 'amenity' => $amenity];
                        $this->containerUtility->convertRequestKeysToSetters($requestKeys, $amenityMeasure);
                        $em->persist($amenityMeasure);
                    } else {
                        throw new ResourceNotFoundException('invalidAmenity');
                    }
                }
            }
            //$em->flush();
        }
    }

    /**
     * Deletes object
     * @param Apartment $apartment
     * @return void
     * @throws \AccessDeniedException
     */
    public function deleteObject(Apartment $apartment, string $locale): void
    {
        $em = $this->doctrine->getManager();
        //check if contract is active or has active tenants
        if ($em->getRepository(PropertyUser::class)->activeTenantCount($apartment->getIdentifier(), 'apartment') > 0) {
            // send email and delete contracts
            $this->notifyTenant($apartment, $locale);
        }
        $em->getRepository(ObjectContractDetail::class)->removeObjectDetails($apartment);
        $em->getRepository(ObjectAmenityMeasure::class)->removeAmenityMeasures($apartment);
        $em->getRepository(Damage::class)->deleteDamage($apartment);
        $em->getRepository(ObjectContracts::class)->deleteContracts($apartment);
        $em->getRepository(PropertyUser::class)->deleteUsers($apartment);
        $em->getRepository(Folder::class)->deleteChildFolders($apartment->getFolder());
        $apartment->getFolder()->setDeleted(1);
        $apartment->setDeleted(1);
        $apartment->setActive(0);
        $em->persist($apartment);
        $em->flush();

        return;
    }

    /**
     * Get objects of a property
     *
     * @param Property $property
     * @param Request $request
     * @param string $locale
     * @param UserIdentity $user
     * @param Role|null $role
     * @return array
     * @throws \Exception
     */
    public function getObjects(Property $property, Request $request, string $locale, UserIdentity $user, ?Role $role = null): array
    {
        $em = $this->doctrine->getManager();
        $objectFilter = $request->get('showdisabled', 1);
        $objects = $em->getRepository(Apartment::class)->getAllApartments($property, $objectFilter, $request->get('sort'), $request->get('page'), $request->get('filter'), $user, $request->get('sortOrder'), $request->get('count'), $role, $locale);
        return array_map(function ($activeObjects) use ($locale, $request, $property, $em, $user) {
            $apartment = $em->getRepository(Apartment::class)->findOneBy(['publicId' => $activeObjects['publicId']->toRfc4122()]);
            $activeObjects['userCount'] = $em->getRepository(PropertyUser::class)->getActiveUserCount($apartment);
            if ($apartment->getProperty()->getSubscriptionPlan() instanceof SubscriptionPlan) {
                $activeObjects['apartmentMin'] = $apartment->getProperty()->getSubscriptionPlan()->getApartmentMin();
                $activeObjects['apartmentMax'] = $apartment->getProperty()->getSubscriptionPlan()->getApartmentMax();
            }
            $activeObjects['isPropertyActive'] = $property->getActive();
            if ($locale == 'de' && $activeObjects['isSystemGenerated'] == true && $activeObjects['name'] == Constants::SYSTEM_GENERATED_OBJECT) {
                $activeObjects['name'] = Constants::SYSTEM_GENERATED_OBJECT_DE;
                $activeObjects['objectType'] = Constants::SYSTEM_GENERATED_OBJECT_DE;
            }
            $objectContract = $em->getRepository(ObjectContracts::class)->findOneBy(['object' => $apartment, 'deleted' => 0, 'active' => 1]);
            $activeObjects['hasActiveContract'] = ($objectContract) ? true : false;
            $activeObjects['publicId'] = $activeObjects['publicId']->toRfc4122();
            if ($activeObjects['hasActiveContract']) { // active contract type
                $contractType = $em->getRepository(ObjectContractDetail::class)->findActiveContractType($apartment, ucfirst($locale));
                $activeObjects['activeContractType'] = reset($contractType)['name'];
                $activeObjects['tenants'] = $em->getRepository(PropertyUser::class)->getTenants($objectContract, $user);
            } else {
                //latest contract type as its a manytoone
                $activeObjects['activeContractType'] = ($apartment->getObjectContractDetails()[0]) ? call_user_func_array(array($apartment->getObjectContractDetails()[0]->getContractType(), 'getName' . ucfirst($locale)), []) : '';
                $activeObjects['tenants'] = ($apartment->getObjectContracts()[0]) ? $em->getRepository(PropertyUser::class)->getTenants($apartment->getObjectContracts()[0], $user) : [];
            }
            $images = $em->getRepository(Document::class)->findBy(['deleted' => false, 'apartment' => $apartment, 'type' => 'coverImage']);
            if (!empty($images)) {
                foreach ($images as $key => $image) {
                    $activeObjects['coverImage'][$key] = $this->dmsService->getUploadInfo($image, $request->getSchemeAndHttpHost(), false);
                }
            }
            return $activeObjects;
        }, $objects);
    }

    /**
     * Get single object details
     * @param Apartment $apartment
     * @param string $sLocale
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function getObjectDetail(Apartment $apartment, string $sLocale, Request $request): array
    {
        $em = $this->doctrine->getManager();
        $locale = ('de' === $sLocale) ? ucfirst($sLocale) : '';
        $objectArray = array();
        if ($apartment instanceof Apartment) {
            $objectArray['publicId'] = $apartment->getPublicId();
            if ($apartment->getObjectType()) {
                $objectArray['objectType']['id'] = $apartment->getObjectType()->getPublicId();
                $objectArray['objectType']['name'] = call_user_func_array(array($apartment->getObjectType(), 'getName' . $locale), []);
                $objectArray['objectType']['type'] = strtolower($apartment->getObjectType()->getName());
            }
            $objectArray['isSystemGenerated'] = $apartment->getIsSystemGenerated();
            $objectArray['officialNumber'] = $apartment->getOfficialNumber();
            $objectArray['isPropertyActive'] = $apartment->getProperty()->getActive();
            $objectArray['isObjectActive'] = $apartment->getActive();
            $objectArray['objectNumber'] = $apartment->getSortOrder();
            $objectArray['floor']['number'] = ($apartment->getFloor()) ? $apartment->getFloor()->getFloorNumber() : null;
            $objectArray['floor']['id'] = ($apartment->getFloor()) ? $apartment->getFloor()->getPublicId() : null;
            $objectArray['area'] = $apartment->getArea();
            $objectArray['ceilingHeight'] = $apartment->getCeilingHeight();
            $objectArray['maxFloor'] = $apartment->getMaxFloorLoading();
            $objectArray['roomCount'] = $apartment->getRoomCount();
            $objectArray['volume'] = $apartment->getVolume();
            $objectArray['name'] = $apartment->getName();
            if ($sLocale == 'de' && $apartment->getIsSystemGenerated() == true && $objectArray['name'] == Constants::SYSTEM_GENERATED_OBJECT) {
                $objectArray['name'] = Constants::SYSTEM_GENERATED_OBJECT_DE;
            }
            $objectArray['userCount'] = $em->getRepository(PropertyUser::class)->getActiveUserCount($apartment);
            $objectArray['activeObjectCount'] = $em->getRepository(Apartment::class)->getActiveApartmentCount($apartment->getProperty()->getIdentifier());
            $objectArray['totalObjectCount'] = (null !== $apartment->getProperty()->getSubscriptionPlan()) ? $apartment->getProperty()->getSubscriptionPlan()->getApartmentMax() : null;
            if (!empty($apartment->getObjectAmenityMeasures())) {
                $i = 0;
                foreach ($apartment->getObjectAmenityMeasures() as $amenity) {
                    if ($amenity instanceof ObjectAmenityMeasure && $amenity->getDeleted() === false) {
                        $objectArray['amenities'][$i]['publicId'] = $amenity->getAmenity()->getPublicId();
                        $objectArray['amenities'][$i]['value'] = $amenity->getValue();
                        $objectArray['amenities'][$i]['name'] = call_user_func_array(array($amenity->getAmenity(), 'getName' . $locale), []);
                        $objectArray['amenities'][$i]['key'] = $amenity->getAmenity()->getAmenityKey();
                        $objectArray['amenities'][$i]['isInput'] = $amenity->getAmenity()->getIsInput();
                        $i++;
                    }
                }
            }
            if (!empty($apartment->getObjectContractDetails())) {
                foreach ($apartment->getObjectContractDetails() as $contractDetail) {
                    if ($contractDetail instanceof ObjectContractDetail) {
                        $objectArray['totalObjectValue'] = $contractDetail->getTotalObjectValue();
                        $objectArray['additionalCostBuilding'] = $contractDetail->getAdditionalCostBuilding();
                        $objectArray['additionalCostEnvironment'] = $contractDetail->getAdditionalCostEnvironment();
                        $objectArray['additionalCostHeating'] = $contractDetail->getAdditionalCostHeating();
                        $objectArray['additionalCostElevator'] = $contractDetail->getAdditionalCostElevator();
                        $objectArray['additionalCostParking'] = $contractDetail->getAdditionalCostParking();
                        $objectArray['additionalCostRenewal'] = $contractDetail->getAdditionalCostRenewal();
                        $objectArray['additionalCostMaintenance'] = $contractDetail->getAdditionalCostMaintenance();
                        $objectArray['additionalCostAdministration'] = $contractDetail->getAdditionalCostAdministration();
                        if ($contractDetail->getModeOfPayment() instanceof ModeOfPayment) {
                            $objectArray['modeOfPayment'] = $this->formatPaymentData($contractDetail->getModeOfPayment(), $sLocale);
                        }
                        $objectArray['additionalCost'] = $contractDetail->getAdditionalCost();
                        $objectArray['netRentRate'] = $contractDetail->getNetRentRate();
                        $objectArray['netRentRateCurrency'] = ($contractDetail->getNetRentRateCurrency()) ? $contractDetail->getNetRentRateCurrency()->getPublicId() : null;
                        $objectArray['baseIndexDate'] = $contractDetail->getBaseIndexDate();
                        $objectArray['baseIndexValue'] = $contractDetail->getBaseIndexValue();
                        $objectArray['additionalCostCurrency'] = ($contractDetail->getAdditionalCostCurrency()) ? $contractDetail->getAdditionalCostCurrency()->getPublicId() : null;
                        $objectArray['contractType']['name'] = call_user_func_array(array($contractDetail->getContractType(), 'getName' . ucfirst($sLocale)), []);
                        $objectArray['contractType']['publicId'] = $contractDetail->getContractType()->getPublicId();
                        $objectArray['contractType']['type'] = strtolower($contractDetail->getContractType()->getNameEn());
                        $objectArray['baseIndex'] = ($contractDetail->getLandIndex()) ? $contractDetail->getLandIndex()->getPublicId() : null;
                        $objectArray['referenceIndex'] = ($contractDetail->getReferenceRate()) ? $contractDetail->getReferenceRate()->getPublicId() : null;
                    }
                }
            }
            if (!empty($apartment->getObjectContracts())) {
                foreach ($apartment->getObjectContracts() as $contractType) {
                    if ($contractType instanceof ContractTypes) {
                        $objectArray['contract']['publicId'] = $contractType->getPublicId();
                        $objectArray['contract']['name'] = call_user_func_array(array($contractType, 'getName' . ucfirst($sLocale)), []);
                    }
                }
            }

            $documents = $em->getRepository(Document::class)->findBy(['deleted' => false, 'apartment' => $apartment, 'type' => 'apartment']);
            if (!empty($documents)) {
                foreach ($documents as $key => $document) {
                    $objectArray['documents'][$key] = $this->dmsService->getUploadInfo($document, $request->getSchemeAndHttpHost(), false);
                }
            }

            $images = $em->getRepository(Document::class)->findBy(['deleted' => false, 'apartment' => $apartment, 'type' => 'coverImage']);
            if (!empty($images)) {
                foreach ($images as $key => $image) {
                    $objectArray['coverImage'][$key] = $this->dmsService->getUploadInfo($image, $request->getSchemeAndHttpHost(), false);
                }
            }

            $floorPlans = $em->getRepository(Document::class)->findBy(['apartment' => $apartment, 'type' => 'floorPlan', 'deleted' => false]);
            if (!empty($floorPlans)) {
                foreach ($floorPlans as $key => $floorPlan) {
                    $objectArray['floorPlan'][$key] = $this->dmsService->getUploadInfo($floorPlan, $request->getSchemeAndHttpHost(), false);
                }
            }
            $objectArray['folder'] = $apartment->getFolder()->getPublicId();
        }

        return $objectArray;
    }

    /**
     * @param ModeOfPayment $paymentData
     * @param string|null $locale
     * @return array
     */
    public function formatPaymentData(ModeOfPayment $paymentData, ?string $locale): array
    {
        return [
            'created_at' => $paymentData->getCreatedAt(),
            'deleted' => $paymentData->getDeleted(),
            'identifier' => $paymentData->getIdentifier(),
            'public_id' => $paymentData->getPublicId(),
            'name' => ($locale == 'de') ? $paymentData->getNameDe() : $paymentData->getNameEn(),
        ];
    }

    /**
     *
     * @param Apartment $apartment
     * @param Request $request
     * @return void
     * @throws \Exception
     */
    private function preventEditIfActiveContract(Apartment $apartment, Request $request): void
    {
        $em = $this->doctrine->getManager();
        $activeContractType = ($apartment->getObjectContractDetails()[0]) ? call_user_func_array(array($apartment->getObjectContractDetails()[0], 'getContractType'), []) : '';
        $hasActiveContract = ($em->getRepository(ObjectContracts::class)->findOneBy(['object' => $apartment, 'deleted' => 0, 'active' => 1])) ? true : false;
        $contractType = $em->getRepository(ContractTypes::class)->findOneBy(['publicId' => $request->get('contractType'), 'deleted' => 0]);
        if ($hasActiveContract && $contractType && $activeContractType->getPublicId() != $contractType->getPublicId()) {
            throw new \Exception('hasActiveContract');
        }
    }

    /**
     *
     * @param Apartment $apartment
     * @param Request $request
     * @param bool|null $encodedData
     * @return array
     * @throws \Exception
     */
    public function getFloorPlan(Apartment $apartment, Request $request, ?bool $encodedData = false): array
    {
        $em = $this->doctrine->getManager();
        $floorPlanArray = [];
        $floorPlans = $em->getRepository(Document::class)->findBy(['apartment' => $apartment, 'type' => 'floorPlan', 'deleted' => false]);
        if (!empty($floorPlans)) {
            foreach ($floorPlans as $key => $floorPlan) {
                $floorPlanArray['floorPlan'][$key] = $this->dmsService->getUploadInfo($floorPlan, $request->getSchemeAndHttpHost(), $encodedData);
            }
        }

        return $floorPlanArray;
    }

    /**
     *
     * @param Apartment $apartment
     * @param array|null $changeSet
     * @param array|null $log
     * @param ObjectContractDetail|null $objectDetail
     * @return ApartmentLog
     */
    public function updateObjectHistory(Apartment $apartment, ?array $changeSet = [], ?array $log = [], ?ObjectContractDetail $objectDetail = null): ApartmentLog
    {
        if (empty($log)) {
            $log = new ApartmentLog();
            $log->setApartment($apartment)
                ->setArea(isset($changeSet['maxFloorLoading']) ? $changeSet['maxFloorLoading'][0] : $apartment->getArea())
                ->setCreatedBy(isset($changeSet['createdBy']) ? $changeSet['createdBy'][0] : $apartment->getCreatedBy())
                ->setFloor(isset($changeSet['floor']) ? $changeSet['floor'][0] : $apartment->getFloor())
                ->setMaxFloorLoading(isset($changeSet['maxFloorLoading']) ? $changeSet['maxFloorLoading'][0] : $apartment->getMaxFloorLoading())
                ->setName(isset($changeSet['name']) ? $changeSet['name'][0] : $apartment->getName())
                ->setObjectType(isset($changeSet['objectType']) ? $changeSet['objectType'][0] : $apartment->getObjectType())
                ->setOfficialNumber(isset($changeSet['officialNumber']) ? $changeSet['officialNumber'][0] : $apartment->getOfficialNumber())
                ->setRent(isset($changeSet['rent']) ? $changeSet['rent'][0] : $apartment->getRent())
                ->setRoomCount(isset($changeSet['roomCount']) ? $changeSet['roomCount'][0] : $apartment->getRoomCount())
                ->setSortOrder(isset($changeSet['sortOrder']) ? $changeSet['sortOrder'][0] : $apartment->getSortOrder())
                ->setVolume(isset($changeSet['volume']) ? $changeSet['volume'][0] : $apartment->getVolume())
                ->setUpdatedAt(new \DateTime());
        } else {
            $log = $log[0];
        }

        if (null !== $objectDetail) {
            $this->updateObjectDetailsLog($log, $changeSet, $objectDetail);
        }

        return $log;
    }

    /**
     *
     * @param ApartmentLog $log
     * @param array|null $changeSet
     * @param ObjectContractDetail|null $objectDetail
     * @return void
     */
    private function updateObjectDetailsLog(ApartmentLog $log, ?array $changeSet = [], ?ObjectContractDetail $objectDetail = null): void
    {
        if (null !== $objectDetail) {
            $log->setAdditionalCost(isset($changeSet['additionalCost']) ? $changeSet['additionalCost'][0] : $objectDetail->getAdditionalCost())
                ->setAdditionalCostAdministration(isset($changeSet['additionalCostAdministration']) ? $changeSet['additionalCostAdministration'][0] : $objectDetail->getAdditionalCostAdministration())
                ->setAdditionalCostBuilding(isset($changeSet['additionalCostBuilding']) ? $changeSet['additionalCostBuilding'][0] : $objectDetail->getAdditionalCostBuilding())
                ->setAdditionalCostCurrency(isset($changeSet['additionalCostCurrency']) ? $changeSet['additionalCostCurrency'][0] : $objectDetail->getAdditionalCostCurrency())
                ->setAdditionalCostElevator(isset($changeSet['additionalCostElevator']) ? $changeSet['additionalCostElevator'][0] : $objectDetail->getAdditionalCostElevator())
                ->setAdditionalCostEnvironment(isset($changeSet['additionalCostEnvironment']) ? $changeSet['additionalCostEnvironment'][0] : $objectDetail->getAdditionalCostEnvironment())
                ->setAdditionalCostHeating(isset($changeSet['additionalCostHeating']) ? $changeSet['additionalCostHeating'][0] : $objectDetail->getAdditionalCostHeating())
                ->setAdditionalCostMaintenance(isset($changeSet['additionalCostMaintenance']) ? $changeSet['additionalCostMaintenance'][0] : $objectDetail->getAdditionalCostMaintenance())
                ->setAdditionalCostParking(isset($changeSet['additionalCostParking']) ? $changeSet['additionalCostParking'][0] : $objectDetail->getAdditionalCostParking())
                ->setAdditionalCostRenewal(isset($changeSet['additionalCostRenewal']) ? $changeSet['additionalCostRenewal'][0] : $objectDetail->getAdditionalCostRenewal())
                ->setTotalObjectValue(isset($changeSet['totalObjectValue']) ? $changeSet['totalObjectValue'][0] : $objectDetail->getTotalObjectValue())
                ->setNetRentRate(isset($changeSet['netRentRate']) ? $changeSet['netRentRate'][0] : $objectDetail->getNetRentRate())
                ->setReferenceRate(isset($changeSet['referenceRate']) ? $changeSet['referenceRate'][0] : $objectDetail->getReferenceRate())
                ->setLandIndex(isset($changeSet['landIndex']) ? $changeSet['landIndex'][0] : $objectDetail->getLandIndex())
                ->setBaseIndexDate(isset($changeSet['baseIndexDate']) ? $changeSet['baseIndexDate'][0] : $objectDetail->getBaseIndexDate())
                ->setBaseIndexValue(isset($changeSet['baseIndexValue']) ? $changeSet['baseIndexValue'][0] : $objectDetail->getBaseIndexValue())
                ->setContractType(isset($changeSet['contractType']) ? $changeSet['contractType'][0] : $objectDetail->getContractType())
                ->setNetRentRateCurrency(isset($changeSet['netRentRateCurrency']) ? $changeSet['netRentRateCurrency'][0] : $objectDetail->getNetRentRateCurrency())
                ->setModeOfPayment(isset($changeSet['modeOfPayment']) ? $changeSet['modeOfPayment'][0] : $objectDetail->getModeOfPayment())
                ->setUpdatedAt(new \DateTime());
        }

        return;
    }

    /**
     *
     * @param ObjectContractDetail $objectDetail
     * @param array $changeSet
     * @return ApartmentRentHistory
     */
    public function updateRentHistory(ObjectContractDetail $objectDetail, array $changeSet): ApartmentRentHistory
    {
        $rentHistory = new ApartmentRentHistory();
        $rentHistory->
        setAdditionalCost(isset($changeSet['additionalCost']) ? $changeSet['additionalCost'][0] : $objectDetail->getAdditionalCost())
            ->setApartment($objectDetail->getObject())
            ->setBasisLandIndex(isset($changeSet['basisLandIndex']) ? $changeSet['basisLandIndex'][0] : $objectDetail->getLandIndex())
            ->setModeOfPayment(isset($changeSet['modeOfPayment']) ? $changeSet['modeOfPayment'][0] : $objectDetail->getModeOfPayment())
            ->setReferenceRate(isset($changeSet['referenceRate']) ? $changeSet['referenceRate'][0] : $objectDetail->getReferenceRate())
            ->setRent(isset($changeSet['netRentRate']) ? $changeSet['netRentRate'][0] : $objectDetail->getNetRentRate())
            ->setUpdatedAt(new \DateTime());

        return $rentHistory;
    }

    /**
     *
     * @param Apartment $object
     * @param string $locale
     * @return array
     */
    public function getObjectLog(Apartment $object, string $locale): array
    {
        $em = $this->doctrine->getManager();
        $logs = $em->getRepository(ApartmentLog::class)->findBy(['apartment' => $object, 'deleted' => false], ['updatedAt' => 'desc']);
        $logArray = [];
        foreach ($logs as $key => $log) {
            if ($log instanceof ApartmentLog) {
                $logArray[$key]['publicId'] = $log->getPublicId();
                $logArray[$key]['area'] = $log->getArea();
                $logArray[$key]['roomCount'] = $log->getRoomCount();
                $logArray[$key]['rent'] = $log->getRent();
                $logArray[$key]['name'] = $log->getName();
                $logArray[$key]['objectType'] = (null !== $log->getObjectType()) ? $log->getObjectType()->getPublicId() : null;
                $logArray[$key]['sortOrder'] = $log->getSortOrder();
                $logArray[$key]['ceilingHeight'] = $log->getCeilingHeight();
                $logArray[$key]['volume'] = $log->getVolume();
                $logArray[$key]['maxFloorLoading'] = $log->getMaxFloorLoading();
                $logArray[$key]['officialNumber'] = $log->getOfficialNumber();
                $logArray[$key]['floor'] = (null !== $log->getFloor()) ? $log->getFloor()->getPublicId() : null;
                $logArray[$key]['createdBy'] = (null !== $log->getCreatedBy()) ? $log->getCreatedBy()->getPublicId() : null;
                $logArray[$key]['totalObjectValue'] = $log->getTotalObjectValue();
                $logArray[$key]['additionalCostBuilding'] = $log->getAdditionalCostBuilding();
                $logArray[$key]['additionalCostEnvironment'] = $log->getAdditionalCostEnvironment();
                $logArray[$key]['additionalCostHeating'] = $log->getAdditionalCostHeating();
                $logArray[$key]['additionalCostElevator'] = $log->getAdditionalCostElevator();
                $logArray[$key]['additionalCostParking'] = $log->getAdditionalCostParking();
                $logArray[$key]['additionalCostRenewal'] = $log->getAdditionalCostRenewal();
                $logArray[$key]['additionalCostMaintenance'] = $log->getAdditionalCostMaintenance();
                $logArray[$key]['additionalCostAdministration'] = $log->getAdditionalCostAdministration();
                $logArray[$key]['additionalCost'] = $log->getAdditionalCost();
                $logArray[$key]['netRentRate'] = $log->getNetRentRate();
                $logArray[$key]['referenceRate'] = (null !== $log->getReferenceRate()) ? $log->getReferenceRate()->getPublicId() : null;
                $logArray[$key]['landIndex'] = (null !== $log->getLandIndex()) ? $log->getLandIndex()->getPublicId() : null;
                $logArray[$key]['baseIndexDate'] = $log->getBaseIndexDate();
                $logArray[$key]['baseIndexValue'] = $log->getBaseIndexValue();
                $logArray[$key]['contractType'] = (null !== $log->getContractType()) ? $log->getContractType()->getPublicId() : null;
                $logArray[$key]['netRentRateCurrency'] = (null !== $log->getNetRentRateCurrency()) ? $log->getNetRentRateCurrency()->getPublicId() : null;
                $logArray[$key]['additionalCostCurrency'] = (null !== $log->getAdditionalCostCurrency()) ? $log->getAdditionalCostCurrency()->getPublicId() : null;
                $logArray[$key]['modeOfPayment'] = (null !== $log->getModeOfPayment()) ? $log->getModeOfPayment()->getPublicId() : null;
            }
        }

        return $logArray;
    }

    /**
     *
     * @param Apartment $apartment
     * @param string $locale
     * @return array
     */
    public function getRentHistory(Apartment $apartment, string $locale): array
    {
        $data = [];
        $em = $this->doctrine->getManager();
        $histories = $em->getRepository(ApartmentRentHistory::class)->findBy(['apartment' => $apartment], ['createdAt' => 'DESC']);
        foreach ($histories as $history) {
            $data['modeOfPayment'] = ($history->getModeOfPayment()) ? call_user_func_array(array($history->getModeOfPayment(), 'getName' . ucfirst($locale)), []) : '';
            $data['referenceIndex'] = ($history->getReferenceRate()) ? $history->getReferenceRate()->getName() : '';
            $data['additionalCost'] = ($history->getAdditionalCost()) ? $history->getAdditionalCost() : '';
            $data['netRentRate'] = ($history->getRent()) ? $history->getRent() : '';
            $data['landIndex'] = ($history->getBasisLandIndex()) ? $history->getBasisLandIndex() : null;
        }

        return $data;
    }

    /** getObjectUsers
     *
     * function to get list of all users related to given objects
     *
     * @param array|null $objects
     * @param bool|null $format
     * @return array
     */
    public function getObjectUsers(?array $objects, ?bool $format = true): array
    {
        $em = $this->doctrine->getManager();
        $userList = [];
        if (!empty($objects)) {
            foreach ($objects as $objectId) {
                if (is_int($objectId)) {
                    $object = $em->getRepository(Apartment::class)->findOneBy(['identifier' => $objectId]);
                } else {
                    $object = $em->getRepository(Apartment::class)->findOneBy(['publicId' => $objectId]);
                }
                if ($object instanceof Apartment) {
                    $users = $em->getRepository(PropertyUser::class)->getActiveUsers($object->getIdentifier());
                    //$users[] = $this->propertyService->getPropertyAdminOrOwner($object->getProperty(), true);
                    if ($object->getProperty() instanceof Property) {
                        $users[] = $object->getProperty()->getJanitor() instanceof UserIdentity ? $object->getProperty()->getJanitor()->getIdentifier() : null;
                        $users[] = $object->getProperty()->getAdministrator() instanceof UserIdentity ? $object->getProperty()->getAdministrator()->getIdentifier() : null;
                        $users[] = $object->getProperty()->getUser() instanceof UserIdentity ? $object->getProperty()->getUser()->getIdentifier() : null;
                    }
                    $users = array_values(array_filter($users));
                    $partialUserList = $this->formatObjectUsersList($users, $object, $format, []);
                    $userList = array_merge($partialUserList, $userList);
                }
            }
        }
        $tmp = [];
        foreach ($userList as $key => $value) {
            $id = (is_array($value)) ? $value["publicId"] : $value->getIdentifier();
            if (!in_array($id, $tmp)) {
                $tmp[] = $id;
            } else {
                unset($userList[$key]);
            }
        }

        return $userList;
    }

    /**
     * formatObjectUsersList
     *
     * function  to format Object Users List
     *
     * @param array $users
     * @param Apartment $object
     * @param bool $format
     * @param array $userList
     * @return array
     */
    private function formatObjectUsersList(array $users, Apartment $object, bool $format, array $userList): array
    {
        foreach ($users as $userIdentity) {
            $userIdentity = $this->doctrine->getRepository(UserIdentity::class)->findOneBy(['identifier' => $userIdentity]);
            if ($format) {
                $details = $this->userService->getUserData($userIdentity);
                $details['publicId'] = $userIdentity->getPublicId();
                $details['companyName'] = $userIdentity->getCompanyName();
                $details['deviceId'] = $this->userService->getDeviceIds($userIdentity);
                $details['apartment']['publicId'] = $object->getPublicId();
                $details['apartment']['name'] = $object->getName();
                $userList[$userIdentity->getIdentifier()] = $details;
            } else {
                $userList[$userIdentity->getIdentifier()] = $userIdentity;
            }
        }
        return $userList;
    }

    /**
     * planDetails
     *
     * @param Property $property
     * @param int $period
     * @param bool $addApartment
     * @return array $data
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function planDetails(Property $property, int $period, bool $addApartment = true): array
    {
        $today = new \DateTime();
        $em = $this->doctrine->getManager();
        $data['planChange'] = false;
        $currentPlan = $property->getSubscriptionPlan();
        $apartmentCount = (int)$em->getRepository(Apartment::class)->getActiveApartmentCount($property->getId());
        $defaultSubscriptionPeriod = $this->params->get('default_subscription_period');
        # Condition changed by rahul, Needs to cross check
        if (($today > $property->getPlanStartDate())
            && ($today->format('Y-m-d') <= $property->getPlanEndDate()->format('Y-m-d'))
            && $currentPlan->getInitialPlan() != 1 && $apartmentCount >= 0) { // Need to verify this condition
            $apartmentCount = ($addApartment) ? $apartmentCount + 1 : $apartmentCount;
            $subscriptionPeriod = in_array($period, $defaultSubscriptionPeriod) ? $period : $defaultSubscriptionPeriod[0];
            $newPlan = $em->getRepository(SubscriptionPlan::class)->getSubscriptionPlan($apartmentCount, $subscriptionPeriod);
            if ($currentPlan instanceof SubscriptionPlan && $newPlan instanceof SubscriptionPlan && $currentPlan !== $newPlan) {
                $currentAmountPerDay = $currentPlan->getAmount() / $currentPlan->getPeriod();
                $newAmountPerDay = $newPlan->getAmount() / $newPlan->getPeriod();
                $planDaysLeft = $this->getDateInterval($property->getPlanEndDate(), $today);
                $newAmount = ($planDaysLeft + $newPlan->getPeriod()) * $newAmountPerDay;
                $balanceFromPrevious = $planDaysLeft * $currentAmountPerDay;
                $amountToBePaid = number_format(((float)$newAmount - $balanceFromPrevious), 2);
                $data['planChange'] = true;
                $data['plan'] = $newPlan;
                $data['amountToBePayed'] = ($planDaysLeft > 0) ? $amountToBePaid : $newPlan->getAmount();
                $data['amountAlreadyPaid'] = $currentPlan->getAmount();
                if ($data['amountToBePayed'] < 0) {
                    $data['planChange'] = false;
                    $data['plan'] = $currentPlan;
                    $data['amountToBePayed'] = 0.00;
                }
                $data['amountDeducted'] = $balanceFromPrevious;
                $data['daysLeftInPrevious'] = $planDaysLeft;

                $msg['title'] = $this->translator->trans('planChangeTitle', array(), null, $property->getUser()->getLanguage());
                $msg['desc'] = $this->translator->trans('planChangeDesc', array('%planamount%' => $data['amountToBePayed']), null, $property->getUser()->getLanguage());
                if ($property->getRecurring()) {
                    $invoiceDetails = $this->getRecurringInvoiceDetails($property, $period, $newPlan->getStripePlan());
                    $data['amountToBePayedOnNextCycle'] = $invoiceDetails;
                    $msg['desc'] = $this->translator->trans('planChangeDescRec', array('%planamount%' => $invoiceDetails['amount']), null, $property->getUser()->getLanguage());
                }
                $data['message'] = $msg;

            }
        } elseif ($property->getActive() == 0 && $apartmentCount == 0) {
            $newPlan = $em->getRepository(SubscriptionPlan::class)->getSubscriptionPlan(1, $defaultSubscriptionPeriod[0]);
            $data['planChange'] = true;
            $data['plan'] = $newPlan;
            $data['amountToBePayed'] = $newPlan->getAmount();
            $msg['title'] = $this->translator->trans('planChangeTitle', array(), null, $property->getUser()->getLanguage());
            $msg['desc'] = $this->translator->trans('planChangeDesc', array('%planamount%' => $data['amountToBePayed']), null, $property->getUser()->getLanguage());
            $data['message'] = $msg;
        }

        return $data;
    }

    /**
     * getRecurringInvoiceDetails
     *
     * @param Property $property
     * @param int $period
     * @param string $newPlan
     * @return array
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function getRecurringInvoiceDetails(Property $property, int $period, string $newPlan): array
    {
        $curDate = new \DateTime('now');
        $expiringOn = $curDate;
        $subscription = $this->stripe->subscriptions->retrieve($property->getStripeSubscription());
        $items = [
            [
                'id' => $subscription->items->data[0]->id,
                'plan' => $newPlan, # Switch to new plan
            ],
        ];

        if ($period === $property->getSubscriptionPlan()->getPeriod()) {
            $invoice = $this->stripe->invoices->upcoming([
                'customer' => $subscription->customer,
                'subscription' => $property->getStripeSubscription(),
                'subscription_items' => $items,
                'subscription_proration_date' => $curDate->getTimestamp(),
            ]);

            # New Plan Invoice of upcoming payment
            $amount = $invoice->total;
            $expiringOn->setTimestamp($invoice->period_end);
            $currency = $invoice->currency;
        } else {
            $invoice = $this->stripe->invoices->all(["limit" => 2]);
            $amount = $invoice->data[0]->total;
            $expiringOn->setTimestamp($invoice->data[0]->period_end);
            $currency = $invoice->data[0]->currency;
        }

        $data['amount'] = number_format((float)($amount) / 100, 2, '.', '') . ' ' . $currency;
        $data['expiringOn'] = $expiringOn;

        return $data;
    }

    /**
     *
     * @param Apartment $apartment
     * @return void
     */
    public function setObjectStatus(Apartment $apartment)
    {
        $property = $apartment->getProperty();
        $currentPlan = $property->getSubscriptionPlan();
        $activeSubscriptionObjectCount = $currentPlan->getApartmentMax();
        $em = $this->doctrine->getManager();
        $plan = null;
        $currentObjectCount = $em->getRepository(Apartment::class)->countObjects($property);
        if (true === $currentPlan->getInitialPlan()) {
            $apartment->setActive(true);
        } else {
            $plan = $em->getRepository(SubscriptionPlan::class)->getSubscriptionPlanByCount($currentObjectCount);
            $apartment->setActive(false);
        }

        return $plan;
    }

    /**
     *
     * @param Apartment $object
     * @param string $locale
     * @return void
     */
    private function notifyTenant(Apartment $object, string $locale): void
    {
        $em = $this->doctrine->getManager();
        $users = $em->getRepository(PropertyUser::class)->getActiveUsers($object->getIdentifier());
        $param['objectName'] = $object->getName();
        foreach ($users as $user) {
            $this->containerUtility->sendEmail($user, 'TenantNotification', $locale, 'TenantNotification', $param);
        }
    }

    /**
     *
     * @param Property $property
     * @param Request $request
     * @return void
     * @throws \Exception
     */
    public function activateObjects(Property $property, Request $request): void
    {
        $em = $this->doctrine->getManager();
        if ($em->getRepository(Apartment::class)->getActiveApartmentCount($property->getIdentifier()) >= $property->getSubscriptionPlan()->getApartmentMax()) {
            throw new \Exception('objectActivationCountReached');
        }
        if (!empty($objects = $request->get('objects'))) {
            foreach ($objects as $object) {
                $oObject = $em->getRepository(Apartment::class)->findOneBy(['publicId' => $object, 'property' => $property, 'deleted' => 0]);
                if (!$oObject instanceof Apartment) {
                    throw new ResourceNotFoundException('objectNotFound');
                }
                $oObject->setActive(true);
            }
        }
    }

    /**
     *
     * @param string $type
     * @param Property $property
     * @param UserIdentity $user
     * @param Request $request
     * @return void
     */
    public function requestObjectReset(string $type, Property $property, UserIdentity $user, Request $request): void
    {
        $em = $this->doctrine->getManager();
        if (!empty($objects = $request->get('objects'))) {
            foreach ($objects as $object) {
                $oObject = $em->getRepository(Apartment::class)->findOneBy(['publicId' => $object, 'property' => $property, 'deleted' => 0]);
                if (!$oObject instanceof Apartment) {
                    throw new ResourceNotFoundException('objectNotFound');
                }
                if ($type === 'activate') {
                    $oObject->setActive(true);
//                    $object =  new ResetObject();
//                    $object->setApartment($oObject)
//                            ->setReason($request->get('reason'))
//                            ->setRequestedBy($user)
//                            ->setProperty($property)
//                            ->setIsSuperAdminApproved(false);
                } else {
                    $oObject->setActive(false);
                }
                $em->persist($oObject);
            }
        }
    }

    /**
     *
     * @param string|null $property
     * @param string|null $object
     * @return array
     */
    public function validateData(?string $property = null, ?string $object = null): array
    {
        $em = $this->doctrine->getManager();
        $oObject = $oProperty = null;
        $oProperty = $em->getRepository(Property::class)->findOneBy(['publicId' => $property, 'deleted' => 0]);
        if (!$oProperty instanceof Property) {
            throw new ResourceNotFoundException('invalidProperty');
        }
        if (!is_null($object)) {
            $oObject = $em->getRepository(Apartment::class)->findOneBy(['publicId' => $object, 'property' => $oProperty, 'deleted' => 0]);
            if (!$oObject instanceof Apartment) {
                throw new ResourceNotFoundException('objectNotFound');
            }
        }

        return ['object' => $oObject, 'property' => $oProperty];
    }

    /**
     *
     * @param Property $property
     * @param UserIdentity $user
     * @param Request $request
     * @return void
     * @throws \Exception
     */
    public function saveGeneralObjectInfo(Property $property, UserIdentity $user, Request $request): void
    {
        $em = $this->doctrine->getManager();
        $objectType = $em->getRepository(ObjectTypes::class)->findOneBy(['name' => Constants::SYSTEM_GENERATED_OBJECT]);
        $request->request->set('objectType', $objectType->getPublicId());
        $request->request->set('name', Constants::SYSTEM_GENERATED_OBJECT);
        $apartment = new Apartment();
        $apartment->setIsSystemGenerated(true);
        $apartment->setName(Constants::SYSTEM_GENERATED_OBJECT);
        $apartment->setProperty($property);
        $apartment->setActive(true);
        $this->saveObjectInfo($property, $apartment, $request, $user);
    }

    /**
     *
     * @param array $apartments
     * @return array
     */
    public function getContractUsers(array $apartments): array
    {
        $em = $this->doctrine->getManager();
        return $em->getRepository(PropertyUser::class)->getActiveContractorIdentifiers($apartments);
    }
}
