<?php


namespace App\Service;

use App\Entity\Folder;
use App\Entity\ObjectContracts;
use App\Entity\PushNotification;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use App\Utils\ContainerUtility;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Utils\GeneralUtility;
use App\Entity\PropertyGroupMapping;
use App\Utils\Constants;
use App\Entity\Apartment;
use App\Entity\UserIdentity;
use App\Entity\RentalTypes;
use App\Entity\NoticePeriod;
use App\Entity\PropertyUser;
use App\Entity\Role;
use App\Entity\Directory;
use App\Entity\Property;
use App\Entity\Document;
use App\Entity\ObjectContractsLog;
use App\Entity\ObjectContractsLogUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Entity\User;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * TenantService
 *
 * Tenant service actions.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class TenantService
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
     *
     * @var PropertyService
     */
    private PropertyService $propertyService;

    /**
     *
     * @var DMSService
     */
    private DMSService $dmsService;

    /**
     *
     * @var User
     */
    private $user;

    /**
     * @var TranslatorInterface $translator
     */
    private TranslatorInterface $translator;

    /**
     * @var UserService
     */
    private UserService $userService;

    /**
     * TenantService constructor.
     * @param ManagerRegistry $doctrine
     * @param ContainerUtility $containerUtility
     * @param ParameterBagInterface $params
     * @param GeneralUtility $generalUtility
     * @param \App\Service\PropertyService $propertyService
     * @param \App\Service\DMSService $dmsService
     * @param TokenStorageInterface $tokenStorage
     * @param TranslatorInterface $translator
     * @param UserService $userService
     */
    public function __construct(ManagerRegistry $doctrine, ContainerUtility $containerUtility,
                                ParameterBagInterface $params, GeneralUtility $generalUtility,
                                PropertyService $propertyService, DMSService $dmsService,
                                TokenStorageInterface $tokenStorage, TranslatorInterface $translator,
                                UserService $userService)
    {
        $this->doctrine = $doctrine;
        $this->containerUtility = $containerUtility;
        $this->params = $params;
        $this->generalUtility = $generalUtility;
        $this->propertyService = $propertyService;
        $this->dmsService = $dmsService;
        $this->user = ($tokenStorage->getToken()) ? $tokenStorage->getToken()->getUser() : '';
        $this->translator = $translator;
        $this->userService = $userService;
    }

    /**
     * @param Property $property
     * @param Apartment $apartment
     * @param ObjectContracts $contract
     * @param Request $request
     * @param UserIdentity $createdBy
     * @param string $locale
     * @param string $currentRole
     * @return ObjectContracts
     * @throws \Exception
     */
    public function saveContract(Property $property, Apartment $apartment, ObjectContracts $contract, Request $request,
                                 UserIdentity $createdBy, string $locale, string $currentRole): ObjectContracts
    {
        $em = $this->doctrine->getManager();
        $contractPeriod = $em->getRepository(RentalTypes::class)->findOneBy(['publicId' => $request->get('contractPeriodType'), 'deleted' => 0]);
        $this->checkIfNewContractAllowed($contractPeriod, $apartment, $request);
        if ($noticePeriod = $em->getRepository(NoticePeriod::class)->findOneBy(['publicId' => $request->get('noticePeriod'), 'deleted' => 0])) {
            $contract->setNoticePeriod($noticePeriod);
        }
        if ($contractPeriod instanceof RentalTypes) {
            $contract->setRentalType($contractPeriod);
        }
        $contract->setObject($apartment);
        if (new \DateTime($request->get('startDate')) > new \DateTime("now")) {
            $contract->setActive(false)
                ->setStatus(Constants::CONTRACT_STATUS_FUTURE);
        } else {
            // just to be make sure no conflicts are happening
            $this->preventIfAnotherActive($apartment);
            $contract->setActive(true)
                ->setStatus(Constants::CONTRACT_STATUS_ACTIVE);
        }

        $em->persist($contract);
        if (!$role = $em->getRepository(Role::class)->findOneBy(['roleKey' => $request->get('role')])) {
            throw new \Exception('invalidRole');
        }
        $this->saveContractUsers($property, $request, $contract, $apartment, $locale, $role, $createdBy);
        if (!empty($request->request->get('documents'))) {
            $this->dmsService->persistDocument($request->request->get('documents'), $contract, 'documents');
        }
        $this->sendEmailAndNotification($request, $currentRole, $apartment, $createdBy);

        return $contract;
    }

    /**
     * @param Request $request
     * @param string $role
     * @param Apartment $apartment
     * @param UserIdentity $currentUser
     * @throws \Exception
     */
    public function sendEmailAndNotification(Request $request, string $role, Apartment $apartment, UserIdentity $currentUser)
    {
        $tenants = $request->get('tenants');
        $em = $this->doctrine->getManager();
        foreach ($tenants as $tenant) {
            if (in_array($this->dmsService->convertSnakeCaseString($tenant['role']),
                [Constants::OBJECT_OWNER_ROLE, Constants::TENANT_ROLE, Constants::TYPE_INDIVIDUAL])) {
                $directory = $em->getRepository(Directory::class)->findOneBy(['publicId' => $tenant['id']]);
                $user = $directory->getUser();
            } else {
                $user = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $tenant['id']]);
            }
            $this->sendNotificationToContractUser($user, $role, $apartment, $currentUser, $tenant['role']);
        }
    }

    /**
     * @param UserIdentity $toUser
     * @param string $role
     * @param Apartment $apartment
     * @param UserIdentity $currentUser
     * @param string|null $contractUserRole
     * @throws \Exception
     */
    public function sendNotificationToContractUser(UserIdentity $toUser, string $role, Apartment $apartment,
                                                   UserIdentity $currentUser, ?string $contractUserRole = null)
    {
        $locale = $toUser->getLanguage() ?? $this->params->get('default_language');
        $emailData['locale'] = $locale;
        $contractUserRole = !is_null($contractUserRole) ?
            $this->dmsService->convertSnakeCaseString($contractUserRole) :
            Constants::TENANT_ROLE;
        $emailContent1 = 'contractTenantContentLine1';
        $emailContent2 = 'contractTenantContentLine2';
        $mailSubject = 'contractTenantSubject';
        if ($contractUserRole === Constants::OBJECT_OWNER_ROLE) {
            $emailContent1 = 'contractObjectOwnerContentLine1';
            $emailContent2 = 'contractObjectOwnerContentLine2';
            $mailSubject = 'contractObjectOwnerSubject';
        }
        $emailData['content1'] = $this->translator->trans($emailContent1, [], null, $locale)
            . ' ' . $currentUser->getFirstName() . ' ' . $currentUser->getLastName();
        $emailData['content2'] = $this->translator->trans($emailContent2, [], null, $locale);
        $emailData['mailSubject'] = $this->translator->trans($mailSubject, [], null, $locale);
        $emailData['apartment'] = $apartment;
        $mailSubject = $this->translator->trans($mailSubject, [], null, $locale);
        $this->containerUtility->sendEmail($toUser, 'RentalContract', $locale, $emailData['mailSubject'], $emailData);
        $this->sendPushNotification($toUser, $mailSubject, $role, $apartment);
    }

    /**
     * sendPushNotification
     *
     * @param UserIdentity $userObj
     * @param string $subject
     * @param string $userRole
     * @param Apartment $apartment
     * @return void
     * @throws \Exception
     */
    public function sendPushNotification(UserIdentity $userObj, string $subject, string $userRole, Apartment $apartment): void
    {
        $deviceIds = $this->userService->getDeviceIds($userObj);
        $em = $this->doctrine->getManager();
        $role = $em->getRepository(Role::class)->findOneBy(['roleKey' => $userRole]);
        $params = array('property' => $apartment->getProperty(), 'toUser' => $userObj, 'message' => $subject, 'event' => 'RENTAL_CONTRACT_ADD',
            'role' => $role, 'createdAt' => new \DateTime());
        $notification = $this->containerUtility->convertRequestKeysToSetters($params, new PushNotification());
        $notificationId = $notification->getPublicId();
        if (!empty($deviceIds)) {
            $notificationParams = array("damageId" => null, 'apartmentId' => $apartment->getPublicId(), 'userRole' => $userRole, "message" => $subject,
                "event" => 'RENTAL_CONTRACT_ADD', 'notificationId' => $notificationId, 'propertyId' => $apartment->getProperty()->getPublicId());
            $this->containerUtility->sendPushNotification($notificationParams, $deviceIds);
        }
    }

    /**
     *
     * @param Property $property
     * @param Apartment $apartment
     * @param ObjectContracts $contract
     * @param Request $request
     * @param UserIdentity $createdBy
     * @param string $locale
     * @param string $currentRole
     * @param type $edit
     * @return ObjectContracts
     * @throws \Exception
     */
    public function editContract(Property $property, Apartment $apartment, ObjectContracts $contract,
                                 Request $request, UserIdentity $createdBy, string $locale,
                                 string $currentRole, $edit = true): ObjectContracts
    {
        $em = $this->doctrine->getManager();
        if (!$role = $em->getRepository(Role::class)->findOneBy(['roleKey' => $request->get('role')])) {
            throw new \Exception('invalidRole');
        }
        $contractPeriod = $em->getRepository(RentalTypes::class)->findOneBy(['publicId' => $request->get('contractPeriodType'), 'deleted' => 0]);
        if (!$contract->getActive()) { // dont check for active contract
            $this->checkIfNewContractAllowed($contractPeriod, $apartment, $request, $contract);
        }
        if ($noticePeriod = $em->getRepository(NoticePeriod::class)->findOneBy(['publicId' => $request->get('noticePeriod'), 'deleted' => 0])) {
            $contract->setNoticePeriod($noticePeriod);
        }
        if ($contractPeriod instanceof RentalTypes) {
            $contract->setRentalType($contractPeriod);
        }
        if (!empty($request->get('tenants'))) {
            $users = $em->getRepository(PropertyUser::class)->findBy(['object' => $apartment, 'contract' => $contract, 'deleted' => false, 'isActive' => true]);
            if (!empty($users)) {
                foreach ($users as $user) {
                    $user->setIsActive(false);
                }
                $em->flush();
            }
        }
        if (!empty($request->request->get('documents'))) {
            $this->dmsService->persistDocument($request->request->get('documents'), $contract, 'documents');
        }
        $this->saveContractUsers($property, $request, $contract, $apartment, $locale, $role, $createdBy, $edit);
        $this->sendEmailAndNotification($request, $currentRole, $apartment, $createdBy);

        return $contract;
    }

    /**
     *
     * @param Property $property
     * @param Request $request
     * @param ObjectContracts $contract
     * @param Apartment $object
     * @param string $locale
     * @param Role $role
     * @param UserIdentity $createdBy
     * @param type $edit
     * @return void
     * @throws \Exception
     */
    private function saveContractUsers(Property $property, Request $request, ObjectContracts $contract, Apartment $object, string $locale, Role $role, UserIdentity $createdBy, $edit = false): void
    {

        $em = $this->doctrine->getManager();
        if (!$role = $em->getRepository(Role::class)->findOneBy(['roleKey' => $request->get('role')])) {
            throw new \Exception('invalidRole');
        }
        $tenantCount = count($request->get('tenants'));
        $tenantLimit = $this->params->get('tenant_limit');
        if ($tenantCount > $tenantLimit) {
            throw new \Exception('tenantLimitExceeded');
        }
        $this->saveTenants($property, $request, $contract, $object, $role, $createdBy, $locale, $edit);
    }

    /**
     *
     * @param Property $property
     * @param Request $request
     * @param ObjectContracts $contract
     * @param Apartment $object
     * @param Role $role
     * @param UserIdentity $createdBy
     * @param string|null $locale
     * @param type $edit
     * @return void
     * @throws \Exception
     */
    private function saveTenants(Property $property, Request $request, ObjectContracts $contract, Apartment $object,
                                 Role $role, UserIdentity $createdBy, ?string $locale = null, $edit = false): void
    {
        $em = $this->doctrine->getManager();
        $userArray = [];
        $activeTenants = ($edit) ? $this->activeTenants($contract, $createdBy) : null;
        $folderName = '';
        if (!empty($request->get('tenants'))) {
            $tenantCount = 0;
            foreach ($request->get('tenants') as $key => $tenant) {
                if (!in_array($tenant['role'], Constants::USER_ROLES)) {
                    throw new \Exception('invalidTenantType');
                }
                $entity = (in_array($this->dmsService->convertSnakeCaseString($tenant['role']), [Constants::OBJECT_OWNER_ROLE, Constants::TENANT_ROLE, Constants::TYPE_INDIVIDUAL])) ?
                    '\\App\\Entity\\Directory' :
                    '\\App\\Entity\\UserIdentity';
                $user = $em->getRepository($entity)->findOneBy(['publicId' => $tenant['id']]);
                $directory = $user;
                if (!$user instanceof Directory) {
                    throw new \Exception('invalidUser');
                } else {
                    $user = $user->getUser();
                }
                $pinned = false;
                if ($key === 0) {
                    $pinned = true;
                    $folderName = $directory instanceof Directory ? $directory->getFirstName() . ' ' . $directory->getLastName() :
                        $user->getFirstName() . ' ' . $user->getLastName();
                }
                $userArray[] = $user->getIdentifier();
                $user->addRole($role);
                $em->refresh($contract);
                $propertyUser = $em->getRepository(PropertyUser::class)->findOneBy(
                    ['user' => $user, 'property' => $property, 'deleted' => 0]);
                if (($propertyUser instanceof PropertyUser && $propertyUser->getContract() instanceof ObjectContracts)
                    || !$propertyUser instanceof PropertyUser) {
                    $propertyUser = new PropertyUser();
                    $this->containerUtility->convertRequestKeysToSetters(['property' => $property, 'isActive' => 1, 'role' => $role, 'object' => $object, 'user' => $user, 'contract' => $contract, 'isPinnedUser' => $pinned], $propertyUser);
                } else {
                    $propertyUser->setContract($contract);
                    $propertyUser->setObject($object);
                    $propertyUser->setRole($role);
                    $propertyUser->setIsPinnedUser(true);
                }
                $tenantCount++;
            }
            $em->flush();

            $countText = (--$tenantCount !== 0) ? ' + ' . '(' . $tenantCount . ' ' . $this->translator->trans('others') . ')' : '';
            $folderName = !empty($folderName) ? $folderName . $countText : '';
            // create folder and update contract entity
            if ($edit) {
                $existingFolder = $contract->getFolder();
                $existingFolder->setDisplayName(ucfirst($folderName));
            } else {
                $parent = $object->getFolder()->getPublicId();
                $folder = $this->dmsService->createFolder($folderName, $createdBy, true, $parent, false);
                $folder = reset($folder);
                if (!empty($folder)) {
                    $folder = $em->getRepository(Folder::class)->findOneBy(['identifier' => $folder['identifier']]);
                    $contract->setFolder($folder);
                }
            }
            if ($edit) {
                $this->getTenantDiff($contract, $activeTenants, $userArray);
            }
        } else {
            throw new \Exception('selectAtleastOneUser');
        }
    }

    /**
     *
     * @param string $locale
     * @param Apartment $object
     * @param UserIdentity $user
     * @return void
     */
    private function sendContractorEmail(string $locale, Apartment $object, UserIdentity $user): void
    {
        $objectType = ($locale === 'de') ? $object->getObjectType()->getNameDe() : $object->getObjectType()->getName();
        $param['objectType'] = $objectType;
        $param['objectName'] = $object->getName();
        $this->containerUtility->sendEmail($user, 'TenantInvitation', $locale, 'TenantInvitation', $param);
    }

    /**
     *
     * @param Apartment $object
     * @param Request $request
     * @param string $locale
     * @param UserIdentity $user
     * @return array
     */
    public function listContracts(Apartment $object, Request $request, string $locale, UserIdentity $user): array
    {
        $em = $this->doctrine->getManager();
        $lists = $em->getRepository(ObjectContracts::class)->getContractList($object, ucfirst($locale), $request->get('sort'), $request->get('sortOrder'), $request->get('count'), $request->get('page'));
        return array_map(function ($list) use ($object, $em, $locale, $user) {
            $contract = $em->getRepository(ObjectContracts::class)->findOneBy(['publicId' => $list['publicId']]);
            if ($contract instanceof ObjectContracts) {
                $list['isPropertyActive'] = $contract->getObject()->getProperty()->getActive();
                $list['contractType'] = ($locale == 'de') ? $contract->getObject()->getObjectContractDetails()[0]->getContractType()->getNameDe() . ' Vertrag'
                    : $contract->getObject()->getObjectContractDetails()[0]->getContractType()->getNameEn() . ' Contract';
                $list['tenants'] = $this->getFormattedTenants($em->getRepository(PropertyUser::class)->getTenants($contract, $user));
            }
            return $list;
        }, $lists);
    }

    /**
     *
     * @param ObjectContracts $contract
     * @param string $locale
     * @param Request $request
     * @param UserIdentity $user
     * @return array
     * @throws \Exception
     */
    public function getContractDetail(ObjectContracts $contract, string $locale, Request $request, UserIdentity $user): array
    {
        $em = $this->doctrine->getManager();
        $objectArray = array();
        if ($contract instanceof ObjectContracts) {
            $objectArray['publicId'] = $contract->getPublicId();
            $objectArray['objectType']['id'] = $contract->getObject()->getObjectType()->getPublicId();
            $objectArray['objectType']['name'] = call_user_func_array(array($contract->getObject()->getObjectType(), ($locale === 'en') ? 'getName' : 'getName' . ucfirst($locale)), []);
            $objectArray['additionalComment'] = $contract->getAdditionalComment();
            $objectArray['active'] = $contract->getActive();
            $objectArray['isPropertyActive'] = $contract->getObject()->getProperty()->getActive();
            $objectArray['status'] = $contract->getStatus();
            $objectArray['ownerVote'] = $contract->getOwnerVote();
            $objectArray['terminationDate'] = $contract->getTerminationDate();
            $objectArray['noticeReceiptDate'] = $contract->getNoticeReceiptDate();
            $objectArray['startDate'] = $contract->getStartDate();
            $objectArray['endDate'] = $contract->getEndDate();
            if (null !== $contract->getNoticePeriod()) {
                $objectArray['noticePeriod']['id'] = $contract->getNoticePeriod()->getPublicId();
                $objectArray['noticePeriod']['name'] = call_user_func_array(array($contract->getNoticePeriod(), 'getName' . ucfirst($locale)), []);
            }
            $objectArray['rentalType'] = ($contract->getRentalType()) ? $contract->getRentalType()->getPublicId() : '';
            $objectArray['tenants'] = $this->getFormattedTenants($em->getRepository(PropertyUser::class)->getTenants($contract, $user));
            $documents = $em->getRepository(Document::class)->findBy(['contract' => $contract, 'deleted' => false]);
            if (!empty($documents)) {
                foreach ($documents as $key => $document) {
                    $objectArray['documents'][$key] = $this->dmsService->getUploadInfo($document, $request->getSchemeAndHttpHost(), false);
                }
            }
        }
        $objectArray['folder'] = $contract->getFolder()->getPublicId();

        return $objectArray;
    }

    /**
     *
     * @param ObjectContracts $contract
     * @return void
     * @throws \Exception
     */
    public function deleteContract(ObjectContracts $contract): void
    {
        // check if the contract is active or contract period is active
        if (true === $contract->getActive() || $contract->getStatus() === Constants::CONTRACT_STATUS_ACTIVE) {
            throw new \Exception('activeContractFail');
        }
        $em = $this->doctrine->getManager();
        $users = $em->getRepository(PropertyUser::class)->findBy(['contract' => $contract]);
        foreach ($users as $user) {
            $user->setDeleted(true);
            $user->setIsActive(0);
        }
        $contract->setDeleted(true);
        $contract->getFolder()->setDeleted(true);
    }

    /**
     *
     * @param ObjectContracts $contract
     * @param Request $request
     * @param UserIdentity $user
     * @throws \Exception
     */
    public function terminateContract(ObjectContracts $contract, Request $request, UserIdentity $user)
    {
        $now = new \DateTime();
        $terminationDate = new \DateTime($request->get('terminationDate'));
        if ($terminationDate < $now) {
            throw new \Exception('terminatinDateCantBePast');
        }
        $contract
            ->setNoticeReceiptDate(new \DateTime($request->get('noticeReceiptDate')))
            ->setTerminationDate(new \DateTime($request->get('terminationDate')))
            ->setTerminatedBy($user)
            ->setStatus(Constants::CONTRACT_STATUS_TERMINATED);
        if ($contract->getEndDate() != new \DateTime($request->get('terminationDate'))) {
            $contract->setActualEndDate($contract->getEndDate())
                ->setEndDate(new \DateTime($request->get('terminationDate')));
        }
    }

    /**
     *
     * @param ObjectContracts $oContract
     * @param array|null $changeSet
     * @param array $userArray
     * @return array
     */
    public function updateContractHistory(ObjectContracts $oContract, ?array $changeSet = [], array $userArray = []): array
    {
        $em = $this->doctrine->getManager();
        $result = [];
        $log = new ObjectContractsLog();
        $user = (!empty($this->user)) ? $this->user->getUserIdentity() : null;
        $log->setContract($oContract)
            ->setUpdatedBy(isset($changeSet['updatedBy']) ? $changeSet['updatedBy'][0] : $user)
            ->setRentalType(isset($changeSet['rentalType']) ? $changeSet['rentalType'][0] : $oContract->getRentalType())
            ->setAdditionalComment(isset($changeSet['additionalComment']) ? $changeSet['additionalComment'][0] : $oContract->getAdditionalComment())
            ->setNoticePeriod(isset($changeSet['noticePeriod']) ? $changeSet['noticePeriod'][0] : $oContract->getNoticePeriod())
            ->setNoticeReceiptDate(isset($changeSet['noticeReceiptDate']) ? $changeSet['noticeReceiptDate'][0] : $oContract->getNoticeReceiptDate())
            ->setOwnerVote(isset($changeSet['ownerVote']) ? $changeSet['ownerVote'][0] : $oContract->getOwnerVote())
            ->setTerminatedBy(isset($changeSet['terminatedBy']) ? $changeSet['terminatedBy'][0] : $oContract->getTerminatedBy())
            ->setTerminationDate(isset($changeSet['terminationDate']) ? $changeSet['terminationDate'][0] : $oContract->getTerminationDate())
            ->setEndDate(isset($changeSet['endDate']) ? $changeSet['endDate'][0] : $oContract->getEndDate())
            ->setStartDate(isset($changeSet['startDate']) ? $changeSet['startDate'][0] : $oContract->getStartDate())
            ->setStatus(isset($changeSet['status']) ? $changeSet['status'][0] : $oContract->getStatus())
            ->setUpdatedAt(new \DateTime());
        $result[] = $log;
        if (!empty($userArray)) { // change in contract users
            foreach ($userArray as $user) {
                $userIdentity = $em->getRepository(UserIdentity::class)->findOneByIdentifier($user);
                $logUser = new ObjectContractsLogUser();
                $logUser->setContract($oContract)
                    ->setLog($log)
                    ->setUser($userIdentity)
                    ->setUpdatedAt(new \DateTime());
                $result[] = $logUser;
            }
        }

        return $result;
    }

    /**
     *
     * @param ObjectContracts $contract
     * @param array $activeTenants
     * @param array $userArray
     * @return void
     */
    public function getTenantDiff(ObjectContracts $contract, array $activeTenants, array $userArray): void
    {
        $diff = (count($userArray) === count($activeTenants) && empty(array_diff($activeTenants, $userArray)) && empty(array_diff($userArray, $activeTenants)));
        if (false === $diff) {
            $users = $this->updateContractHistory($contract, [], $activeTenants);
            if (!empty($users)) {
                $em = $this->doctrine->getManager();
                foreach ($users as $user) {
                    if (is_array($user)) {
                        foreach ($user as $item) {
                            if (is_object($item)) {
                                $em->persist($item);
                            }
                        }
                    }

                    if (is_object($user)) {
                        $em->persist($user);
                    }
                }

                $users = [];
                $em->flush();
            }
        }

        return;
    }

    /**
     *
     * @param ObjectContracts $contract
     * @param UserIdentity $user
     * @return array
     */
    private function activeTenants(ObjectContracts $contract, UserIdentity $user): array
    {
        $em = $this->doctrine->getManager();
        $tenants = $em->getRepository(PropertyUser::class)->getTenants($contract, $user);
        $tenantArray = [];
        foreach ($tenants as $tenant) {
            $user = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $tenant['userPublicId']]);
            $tenantArray[] = $user->getUser()->getIdentifier();
        }

        return $tenantArray;
    }

    /**
     *
     * @param ObjectContracts $contract
     * @param string $locale
     * @return array
     */
    function getContractLog(ObjectContracts $contract, string $locale): array
    {
        $em = $this->doctrine->getManager();
        $logs = $em->getRepository(ObjectContractsLog::class)->findBy(['contract' => $contract, 'deleted' => false], ['updatedAt' => 'desc']);
        $logArray = [];
        foreach ($logs as $key => $log) {
            $logArray[$key]['publicId'] = $log->getPublicId();
            $logArray[$key]['additionalComment'] = $log->getAdditionalComment();
            $logArray[$key]['ownerVote'] = $log->getOwnerVote();
            $logArray[$key]['startDate'] = $log->getStartDate();
            $logArray[$key]['endDate'] = $log->getEndDate();
            $logArray[$key]['terminationDate'] = $log->getTerminationDate();
            $logArray[$key]['noticeReceiptDate'] = $log->getNoticeReceiptDate();
            if (null !== $log->getNoticePeriod()) {
                $logArray[$key]['noticePeriod']['id'] = $log->getNoticePeriod()->getPublicId();
                $logArray[$key]['noticePeriod']['name'] = call_user_func_array(array($log->getNoticePeriod(), 'getName' . ucfirst($locale)), []);
            }
            $logArray[$key]['rentalType'] = ($log->getRentalType()) ? $log->getRentalType()->getPublicId() : '';
            $users = $em->getRepository(ObjectContractsLogUser::class)->findBy(['log' => $log, 'deleted' => false]);
            if (!empty($users)) {
                foreach ($users as $keyUser => $user) {
                    $logArray[$key]['user'][$keyUser]['name'] = $user->getUser()->getFirstName() . ' ' . $user->getUser()->getLastName();
                    $logArray[$key]['user'][$keyUser]['publicId'] = $user->getIdentifier();
                }
            }
        }

        return $logArray;
    }

    /**
     *
     * @param ObjectContracts $contract
     * @param Request $request
     * @return bool
     */
    public function checkNoticePeriod(ObjectContracts $contract, Request $request): bool
    {
        if ($contract->getObject()->getObjectContractDetails()[0]->getContractType()->getType() === Constants::OBJECT_CONTRACT_TYPE_OWNER) {
            return true;
        }

        $diff = abs(strtotime($request->get('terminationDate')) - strtotime($request->get('noticeReceiptDate')));
        if ($diff < strtotime($contract->getNoticePeriod()->getType() . ' days', 0)) {
            return false;
        }

        return true;
    }

    /**
     *
     * @param ObjectContracts $contract
     * @param Apartment $apartment
     * @return void
     * @throws \Exception
     */
    public function revokeContract(ObjectContracts $contract, Apartment $apartment): void
    {
        $em = $this->doctrine->getManager();
        if ($contract !== $em->getRepository(ObjectContracts::class)->findOneBy(['object' => $apartment, 'active' => 1, 'deleted' => false])) {
            throw new \Exception('revokeFailed');
        }
        if ($contract->getRentalType()->getType() === Constants::RENTAL_TYPE_OPENEND && $em->getRepository(ObjectContracts::class)->getFutureContractCount($apartment) >= 1) {
            throw new \Exception('revokeFailedWithFutureContract');
        }
        // just to be make sure no conflicts are happening
        $this->preventIfAnotherActive($apartment, $contract);
        $contract->setActive(true)
            ->setStatus(Constants::CONTRACT_STATUS_ACTIVE)
            ->setNoticeReceiptDate(null)
            ->setEndDate($contract->getActualEndDate())
            ->setTerminationDate(null)
            ->setTerminatedBy(null);
    }

    /**
     *
     * @param RentalTypes $contractPeriod
     * @param Apartment $apartment
     * @param Request $request
     * @param ObjectContracts|null $contract
     * @return bool
     * @throws \Exception
     */
    private function checkIfNewContractAllowed(RentalTypes $contractPeriod, Apartment $apartment, Request $request, ?ObjectContracts $contract = null): bool
    {
        $em = $this->doctrine->getManager();
        $isEdit = false;
        if ($contract instanceof ObjectContracts) {
            $contract = $contract->getPublicId();
            $isEdit = true;
        }
        $allContracts = $em->getRepository(ObjectContracts::class)->getAllValidContracts($apartment, $contract);
        $startDate = new \DateTime($request->request->get('startDate'));
        foreach ($allContracts as $individualContract) {
            $activeContract = $em->getRepository(ObjectContracts::class)->find(['identifier' => $individualContract['identifier']]);
            if ($activeContract instanceof ObjectContracts) {
                //checks if dates are not between active contract start and end date
                if ($startDate >= $activeContract->getStartDate() && $startDate <= $activeContract->getEndDate()) {
                    throw new \Exception('dateConflictError');
                }

                //prevent if new start date is before active contracts start date
                if ($startDate <= $activeContract->getStartDate() && $startDate <= $activeContract->getEndDate()) {
                    throw new \Exception('dateConflictError');
                }

                // checks if open end contract is terminated, else prevent adding new
                if ($startDate >= $activeContract->getEndDate()
                    && ($activeContract->getRentalType()->getType() === Constants::RENTAL_TYPE_OPENEND && $activeContract->getStatus() != Constants::CONTRACT_STATUS_TERMINATED)
                    && $contract !== $activeContract) { // future contract
                    throw new \Exception('alreadyHaveContract');
                }

                // checks if at least one open end future contract is there, prevent adding new
                $futureContract = $em->getRepository(ObjectContracts::class)->getFutureContractCount($apartment, $activeContract->getIdentifier());
                if (($contractPeriod->getType() === Constants::RENTAL_TYPE_OPENEND) && $futureContract >= 1 &&
                    $startDate >= $activeContract->getStartDate() &&
                    (!is_null($activeContract->getEndDate()) && $startDate <= $activeContract->getEndDate())) { // future contract
                    throw new \Exception('alreadyHaveFutureContract');
                }
                if ($contractPeriod->getType() === Constants::RENTAL_TYPE_FUTURE && $activeContract->getRentalType()->getType() === Constants::RENTAL_TYPE_OPENEND) { // future contract
                    throw new \Exception('alreadyHaveFutureContract');
                }

                //if fixed can add any number of future without conflicting each dates
                if ($contractPeriod->getType() === Constants::RENTAL_TYPE_FIXED) {
                    if ($startDate <= $activeContract->getEndDate()) {
                        throw new \Exception('dateConflictError');
                    }
                    $futureContracts = $em->getRepository(ObjectContracts::class)->getAllFutureContracts($apartment);
                    foreach ($futureContracts as $futureContract) {
                        if ($startDate >= $futureContract['startDate'] && $startDate <= $futureContract['endDate']) {
                            throw new \Exception('dateConflictError');
                        }
                    }
                }
            } else {
                if ($contractPeriod->getType() === Constants::RENTAL_TYPE_FIXED && $em->getRepository(ObjectContracts::class)->checkOpenRentalContract($apartment, $this->getOpenEnd()) >= 1) { // future contract
                    throw new \Exception('alreadyHaveOpenContract');
                }
                if ($contractPeriod->getType() === Constants::RENTAL_TYPE_OPENEND && $em->getRepository(ObjectContracts::class)->getOpenContracts($apartment) >= 1) { // future contract
                    throw new \Exception('alreadyHaveOpenContract');
                }
            }
        }
        return true;
    }


//    /**
//     *
//     * @param RentalTypes $contractPeriod
//     * @param Apartment $apartment
//     * @param Request $request
//     * @param ObjectContracts|null $contract
//     * @return bool
//     * @throws \Exception
//     */
//    private function checkIfNewContractAllowed(RentalTypes $contractPeriod, Apartment $apartment, Request $request, ?ObjectContracts $contract = null): bool
//    {
//        $em = $this->doctrine->getManager();
//        $isEdit = false;
//        if ($contract instanceof ObjectContracts) {
//            $contract = $contract->getPublicId();
//            $isEdit = true;
//        }
//        $allContracts = $em->getRepository(ObjectContracts::class)->getAllValidContracts($apartment, $contract);
//        $startDate = new \DateTime($request->request->get('startDate'));
//        $endDate = new \DateTime($request->request->get('endDate'));
//
//        foreach ($allContracts as $individualContract) {
//            $activeContract = $em->getRepository(ObjectContracts::class)->find(['identifier' => $individualContract['identifier']]);
//            if ($activeContract instanceof ObjectContracts) {
//                // Check if the new contract's start date or end date falls within an active contract's date range
//                if (($startDate >= $activeContract->getStartDate() && $startDate <= $activeContract->getEndDate()) ||
//                    ($endDate >= $activeContract->getStartDate() && $endDate <= $activeContract->getEndDate())) {
//                    throw new \Exception('dateConflictError');
//                }
//
//                // Check if the new contract's start date is before an active contract's start date
//                if ($startDate < $activeContract->getStartDate() && $endDate >= $activeContract->getStartDate()) {
//                    throw new \Exception('dateConflictError');
//                }
//
//                // Check if an open-end contract is terminated before adding a new contract
//                if ($activeContract->getRentalType()->getType() === Constants::RENTAL_TYPE_OPENEND &&
//                    $activeContract->getStatus() !== Constants::CONTRACT_STATUS_TERMINATED &&
//                    $contract !== $activeContract) {
//                    throw new \Exception('alreadyHaveContract');
//                }
//
//                // Check if there is at least one open-end future contract
//                $futureContract = $em->getRepository(ObjectContracts::class)->getFutureContractCount($apartment, $activeContract->getIdentifier());
//                if ($contractPeriod->getType() === Constants::RENTAL_TYPE_OPENEND && $futureContract >= 1 &&
//                    $startDate >= $activeContract->getStartDate() && $endDate <= $activeContract->getEndDate()) {
//                    throw new \Exception('alreadyHaveFutureContract');
//                }
//
//                // Check if a future contract is being added when an open-end contract exists
//                if ($contractPeriod->getType() === Constants::RENTAL_TYPE_FUTURE &&
//                    $activeContract->getRentalType()->getType() === Constants::RENTAL_TYPE_OPENEND) {
//                    throw new \Exception('alreadyHaveFutureContract');
//                }
//            } else {
//                // Check if a fixed contract is being added when an open-end contract exists
//                if ($contractPeriod->getType() === Constants::RENTAL_TYPE_FIXED &&
//                    $em->getRepository(ObjectContracts::class)->checkOpenRentalContract($apartment, $this->getOpenEnd()) >= 1) {
//                    throw new \Exception('alreadyHaveOpenContract');
//                }
//
//                // Check if an open-end contract is being added when another open-end contract exists
//                if ($contractPeriod->getType() === Constants::RENTAL_TYPE_OPENEND &&
//                    $em->getRepository(ObjectContracts::class)->getOpenContracts($apartment) >= 1) {
//                    throw new \Exception('alreadyHaveOpenContract');
//                }
//            }
//        }
//    }

    /**
     *
     * @param Apartment $object
     * @param Request $request
     * @return bool
     * @throws \Exception
     */
    public function checkStartDate(Apartment $object, Request $request): bool
    {
        $contractToEdit = null;
        if ($request->request->has('contract') && $request->request->get('contract') !== '') {
            $contractToEdit = $request->request->get('contract');
        }
        $requestedRentalType = $request->request->get('rentalType');
        $em = $this->doctrine->getManager();
        $startDate = new \DateTime($request->request->get('startDate'));
        $endDate = $request->request->has('endDate') ? new \DateTime($request->request->get('endDate')) : null;
        $allValidContracts = $em->getRepository(ObjectContracts::class)->getAllValidContracts($object, $contractToEdit);
        foreach ($allValidContracts as $contract) {
            if ($startDate >= $contract['startDate'] && (isset($contract['endDate'])
                    && $startDate <= $contract['endDate'])) {
                return false;
            }
            if ($contract['type'] === Constants::RENTAL_TYPE_OPENEND &&
                $contract['status'] === Constants::CONTRACT_STATUS_TERMINATED &&
                $contract['terminationDate'] >= $startDate) {
                return false;
            }
            if ($requestedRentalType === Constants::RENTAL_TYPE_OPENEND && $startDate <= $contract['startDate']) {
                return false;
            }
            if ($requestedRentalType === Constants::RENTAL_TYPE_FIXED
                && $startDate >= $contract['endDate'] && $endDate >= $contract['startDate']) {
                return false;
            }
        }
        return true;
    }

    /**
     *
     * @return RentalTypes
     */
    private function getOpenEnd(): RentalTypes
    {
        $em = $this->doctrine->getManager();
        return $em->getRepository(RentalTypes::class)->findOneBy(['type' => Constants::RENTAL_TYPE_OPENEND, 'deleted' => 0]);
    }

    /**
     *
     * @return RentalTypes
     */
    private function getFixedTerm(): RentalTypes
    {
        $em = $this->doctrine->getManager();
        return $em->getRepository(RentalTypes::class)->findOneBy(['type' => Constants::RENTAL_TYPE_FIXED, 'deleted' => 0]);
    }

    /**
     * Checks if there is any other contract active
     * @param Apartment $apartment
     * @param ObjectContracts|null $contract
     * @return bool
     * @throws \Exception
     */
    private function preventIfAnotherActive(Apartment $apartment, ?ObjectContracts $contract = null): bool
    {
        $em = $this->doctrine->getManager();
        $activeContract = $em->getRepository(ObjectContracts::class)->findOneBy(['object' => $apartment, 'active' => Constants::CONTRACT_STATUS_ACTIVE, 'deleted' => false]);
        if ($activeContract instanceof ObjectContracts) {
            if (!is_null($contract) && $activeContract === $contract) {
                return true;
            }
            throw new \Exception('alreadyHaveAnActiveContract');
        }

        return true;
    }

    /**
     *
     * @param UserIdentity $user
     * @return void
     */
    public function setFolderName(UserIdentity $user): void
    {
        $em = $this->doctrine->getManager();
        $contracts = $em->getRepository(PropertyUser::class)->findBy(['user' => $user, 'isPinnedUser' => true]);
        $folderName = $user->getFirstName() . ' ' . $user->getLastName();
        foreach ($contracts as $contract) {
            $pinnedContract = $contract->getContract();
            if ($pinnedContract instanceof ObjectContracts) {
                $tenantCount = $em->getRepository(PropertyUser::class)->activeTenantCount($pinnedContract->getIdentifier(), 'contract');
                $countText = (--$tenantCount !== 0) ? ' + ' . '(' . $tenantCount . ' ' . $this->translator->trans('others') . ')' : '';
                $folderName = !empty($folderName) ? $folderName . $countText : '';
                $folder = $pinnedContract->getFolder();
                $folder->setDisplayName(ucfirst($folderName));
            }
        }
    }

    /**
     *
     * @param array $tenants
     * @return array
     */
    private function getFormattedTenants(array $tenants): array
    {
        $em = $this->doctrine->getManager();
        return array_map(function ($tenants) use ($em) {
            $roles = $em->getRepository(UserIdentity::class)->getUserRoles($tenants['userIdentifier'], 'en');
            $roleArray = array_column($roles, 'roleKey');
            if (in_array(Constants::COMPANY_ROLE, $roleArray)) {
                $tenants['isCompanyRole'] = true;
            }
            if (isset($tenants['companyName']) && $tenants['companyName'] !== '') {
                $tenants['name'] = $tenants['companyName'];
            }
            if (isset($tenants['isRegisteredUser']) && $tenants['isRegisteredUser'] == "1") {
                $tenants['isRegisteredUser'] = true;
            } else {
                $tenants['isRegisteredUser'] = false;
            }
            return $tenants;
        }, $tenants);
    }
}
