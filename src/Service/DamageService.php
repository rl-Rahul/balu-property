<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\DamageRequest;
use App\Entity\Category;
use App\Entity\UserIdentity;
use App\Entity\Damage;
use App\Entity\DamageImage;
use App\Entity\DamageStatus;
use App\Utils\ContainerUtility;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Apartment;
use App\Entity\Property;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\DamageLog;
use App\Entity\PropertyUser;
use App\Utils\Constants;
use App\Entity\DamageComment;
use App\Entity\PushNotification;
use App\Entity\DamageAppointment;
use App\Entity\TemporaryUpload;
use App\Utils\GeneralUtility;
use App\Entity\Role;
use App\Helpers\FileUploadHelper;
use App\Entity\DamageOffer;
use App\Entity\DamageDefect;
use App\Exception\FormErrorException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Document;
use App\Utils\FileUploaderUtility;
use App\Entity\Message;
use Doctrine\Common\Collections\Collection;
use DateTimeInterface;

/**
 * Class DamageService
 * @package App\Service
 */
class DamageService extends BaseService
{
    /**
     * @var UserService $userService
     */
    private UserService $userService;

    /**
     * @var PropertyService $propertyService
     */
    private PropertyService $propertyService;

    /**
     * @var DMSService $dmsService
     */
    private DMSService $dmsService;

    /**
     * @var GeneralUtility $generalUtility
     */
    private GeneralUtility $generalUtility;

    /**
     * @var ContainerUtility $containerUtility
     */
    private ContainerUtility $containerUtility;

    /**
     * @var CompanyService $companyService
     */
    private CompanyService $companyService;

    /**
     * @var FileUploadHelper $fileUploadHelper
     */
    private FileUploadHelper $fileUploadHelper;

    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $parameterBag;

    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    /**
     * @var FileUploaderUtility
     */
    private FileUploaderUtility $fileUploaderUtility;

    /**
     * @var ObjectService
     */
    private ObjectService $objectService;

    /**
     * DamageService constructor.
     * @param ContainerUtility $containerUtility
     * @param UserService $userService
     * @param PropertyService $propertyService
     * @param DMSService $dmsService
     * @param GeneralUtility $generalUtility
     * @param CompanyService $companyService
     * @param FileUploadHelper $fileUploadHelper
     * @param TranslatorInterface $translator
     * @param ParameterBagInterface $parameterBag
     * @param EntityManagerInterface $em
     * @param FileUploaderUtility $fileUploaderUtility
     * @param ObjectService $objectService
     */
    public function __construct(ContainerUtility $containerUtility, UserService $userService,
                                PropertyService $propertyService, DMSService $dmsService,
                                GeneralUtility $generalUtility, CompanyService $companyService,
                                FileUploadHelper $fileUploadHelper, TranslatorInterface $translator,
                                ParameterBagInterface $parameterBag, EntityManagerInterface $em,
                                FileUploaderUtility $fileUploaderUtility, ObjectService $objectService)
    {
        $this->containerUtility = $containerUtility;
        $this->userService = $userService;
        $this->propertyService = $propertyService;
        $this->dmsService = $dmsService;
        $this->generalUtility = $generalUtility;
        $this->em = $em;
        $this->parameterBag = $parameterBag;
        $this->translator = $translator;
        $this->companyService = $companyService;
        $this->fileUploadHelper = $fileUploadHelper;
        $this->fileUploaderUtility = $fileUploaderUtility;
        $this->objectService = $objectService;
    }

    /**
     * processDamage
     *
     * function to process damage ticket
     *
     * @param Request $request
     * @param Damage $damage
     * @param UserIdentity $user
     * @param string $currentRole
     * @param bool|null $isEdit
     * @param int $currentCategory
     * @return void || throws Exception
     * @throws \Exception
     */
    public function processDamage(Request $request, Damage $damage, UserIdentity $user, string $currentRole,
                                  ?bool $isEdit = false, $currentCategory = null): void
    {
        $this->validatePermission($request, $currentRole, $user, ($isEdit) ? $damage->getApartment() : null);
        $statusKey = $isEdit ? $damage->getStatus()->getKey() : null;
        $initialDamageStatus = $this->getInitialDamageStatus($request, $currentRole, $user, $isEdit, $statusKey);
        $this->saveDamageInfo($request, $damage, $user, $initialDamageStatus, $isEdit, $currentRole, $currentCategory);
        $this->logDamage($user, $damage);
    }

    /**
     * saveDamageInfo
     *
     * function to save damage ticket
     *
     * @param Request $request
     * @param Damage $damage
     * @param UserIdentity $user
     * @param string $statusKey
     * @param bool $isEdit
     * @param string|null $currentRole
     * @param int $currentCategory
     * @return Damage
     * @throws \Exception
     */
    public function saveDamageInfo(Request $request, Damage $damage, UserIdentity $user, string $statusKey,
                                   bool $isEdit = false, string $currentRole = null, $currentCategory = null): Damage
    {
        $em = $this->em;
        if ($isEdit) {
            $apartment = $damage->getApartment();
            $selectedCategory = $em->getRepository(Category::class)->findOneBy(['publicId' => $request->request->get('issueType')]);
            if ($selectedCategory instanceof Category && ($currentCategory != $selectedCategory->getIdentifier())) {
                $em->getRepository(DamageRequest::class)->markRequestAsDeleted($damage);
            }
            $damage->setUpdatedAt(new \DateTime('now'));
        } else {
            $apartment = $em->getRepository(Apartment::class)->findOneBy(['publicId' => $request->request->get('apartment')]);
        }
        $status = $em->getRepository(DamageStatus::class)->findOneBy(['key' => $statusKey]);
        $requestKeys = ['status' => $status];
        $currentRole = $em->getRepository(Role::class)->findOneBy(['roleKey' => $currentRole]);
        if (!$isEdit) {
            $requestKeys['user'] = $user;
            $requestKeys['createdByRole'] = $currentRole;
        }
        if (strpos($statusKey, 'SEND_TO_COMPANY_')) {
            $requestKeys['companyAssignedBy'] = $user;
            $requestKeys['companyAssignedByRole'] = $currentRole;
        }
        $damageUsers = $this->getTicketUsers($damage, false, $apartment);
        $damage->addUser($user);
        foreach ($damageUsers as $damageUser) {
            $damage->addUser($damageUser);
        }
        if ($request->request->has('allocation') && $request->request->get('allocation') === true) {
            $damageOwner = $apartment->getProperty()->getUser();
        } else {
            $damageOwner = $user;
            $requestKeys['companyAssignedBy'] = $damageOwner;
            if ($currentRole->getRoleKey() === Constants::OWNER_ROLE && $damage->getApartment()->getProperty()->getAdministrator() instanceof UserIdentity) {
                $currentRole = $em->getRepository(Role::class)->findOneBy(['roleKey' => $this->camelCaseConverter(Constants::PROPERTY_ADMIN_ROLE)]);
            }
            $requestKeys['companyAssignedByRole'] = $currentRole;
        }
        $requestKeys['damageOwner'] = $damageOwner;
        $this->containerUtility->convertRequestKeysToSetters($requestKeys, $damage);
        $this->processImages($request, $damage, $user, $isEdit);
        $this->setCurrentUser($damage, $statusKey);

        return $damage;
    }

    /**
     * Generate damage details for a given Damage entity.
     *
     * @param Damage $damage The Damage entity for which to generate details
     * @param Request $request The current request object
     * @param UserIdentity|null $user The current user identity, if available
     * @param bool|null $isList Whether the details are being generated for a list view
     * @param string|null $currentUserRole The current user's role
     *
     * @return array The generated damage details
     * @throws \Exception
     */
    public function generateDamageDetails(Damage $damage, Request $request, ?UserIdentity $user = null, ?bool $isList = false, string $currentUserRole = null): array
    {
        $property = $damage->getApartment()->getProperty();
        $requestType = $request->get('type');
        $damageDetail = $this->damageDetails($request, $damage, $property, $user, $currentUserRole, $isList);

        if ($user instanceof UserIdentity) {
            $damageDetail = $this->handleUserDetails($damage, $user, $currentUserRole, $damageDetail, $isList);
        }

        $damageDetail['companyName'] = $damage->getDamageOffers() ? $this->getCompanyName($damage->getDamageOffers()) : null;
        $damageDetail['pinnedContractor'] = $this->em->getRepository(PropertyUser::class)->getPinnedActiveContractorOfObject(['objects' => $damage->getApartment()->getIdentifier()]);

        if (!$isList || ($isList && !empty($requestType) && $requestType === 'dashboard')) {
            $damageDetail = $this->handleDamageDetails($damage, $request, $property, $damageDetail, $user, $currentUserRole);
        } else {
            $this->getDamagePhotos($request, $damage, $damageDetail);
        }

        return $damageDetail;
    }

    /**
     * Handle user-specific details for the damage.
     *
     * @param Damage $damage The Damage entity
     * @param UserIdentity $user The current user identity
     * @param string $currentUserRole The current user's role
     * @param array $damageDetail The damage details array to be updated
     * @param bool $isList Whether the details are being generated for a list view
     *
     * @return array The updated damage details array
     */
    private function handleUserDetails(Damage $damage, UserIdentity $user, string $currentUserRole, array $damageDetail, bool $isList): array
    {
        $damageDetail['chatOption'] = $this->getChatOptionAvailability($damage, $user, $currentUserRole);
        $readStatus = $this->em->getRepository(Damage::class)->getReadStatus($user, $damage->getIdentifier());
        $damageDetail['isRead'] = $readStatus;

        if (!$readStatus) {
            $this->markAsRead($damage, $user);
            $damageDetail['isRead'] = true;
        }

        if (!$isList) {
            $damageDetail['logs'] = $this->generateDamageLog($damage, $currentUserRole, $user);
            $damageDetail['permissions'] = $this->getPermissions($damage, $user, $currentUserRole);
        }

        return $damageDetail;
    }

    /**
     * Handle damage-specific details for the damage.
     *
     * @param Damage $damage The Damage entity
     * @param Request $request The current request object
     * @param Property $property The Property entity associated with the damage
     * @param array $damageDetail The damage details array to be updated
     * @param UserIdentity|null $user The current user identity, if available
     * @param string $currentUserRole The current user's role
     *
     * @return array The updated damage details array
     * @throws \Exception
     */
    private function handleDamageDetails(Damage $damage, Request $request, Property $property, array $damageDetail, ?UserIdentity $user, string $currentUserRole): array
    {
        $damageDetail['createdAt'] = $damage->getCreatedAt();
        $damageDetail['damageOwner'] = $this->getDamageOwnerDetails($request, $damage, $user, $currentUserRole);
        $damageDetail['status'] = $damage->getStatus() ? $damage->getStatus()->getKey() : null;
        $damageDetail['property'] = $this->formatPropertyDetails($property);
        $damageDetail['apartmentId'] = $damage->getApartment()->getPublicId();
        $damageDetail['messageId'] = $this->getMessageId($damage);
        $damageDetail['address'] = $this->getPropertyAddress($property);
        $damageDetail['preferredCompany'] = $damage->getPreferredCompany() ? $this->companyService->getCompanyDetailArray($damage->getPreferredCompany()) : null;
        $damageDetail['description'] = $damage->getDescription();
        $damageDetail['isDeviceAffected'] = $damage->getIsDeviceAffected();
        $damageDetail['isJanitorEnabled'] = $damage->getIsJanitorEnabled();
        $damageDetail['barCode'] = $damage->getBarCode();
        $damageDetail['janitor'] = $this->getJanitorDetails($property);
        $this->updateTenantInfoInDamageDetails($damageDetail, $damage);
        $damageDetail['owner'] = $this->userService->getFormattedData($property->getUser(), true);
        $damageDetail['admin'] = $this->getAdminDetails($property);
        $damageDetail['company'] = $this->getCompanyDetails($damage);
        $damageDetail['parentCompany'] = $this->getParentCompanyDetails($damage);
        $damageDetail['offer']['publicId'] = $this->getOfferPublicId($damage);
        $damageDetail['defect'] = $this->generateDefectDetails($damage, $request);
        $damageDetail['rating'] = $this->companyService->generateRatingDetails($damage, $request);
        $damageDetail['appointment'] = $this->getAppointmentDetails($damage);
        $damageDetail['activeComment'] = $this->getActiveCommentDetails($damage);
        $damageDetail['confirmationDate'] = $this->getConfirmationDate($damage);
        $damageDetail['internalReferenceNumber'] = $damage->getInternalReferenceNumber();
        $damageDetail['issueType'] = $damage->getIssueType() ? $this->getFormattedIssueType($damage->getIssueType()) : null;
        $damageDetail['allocationType'] = $damage->getAllocation();
        $this->getDamageImages($request, $damage, $damageDetail, false);

        return $damageDetail;
    }

    /**
     * Get the damage owner details for the given damage.
     *
     * @param Request $request The current request object
     * @param Damage $damage The Damage entity
     * @param UserIdentity|null $user The current user identity, if available
     * @param string $currentUserRole The current user's role
     *
     * @return array The damage owner details
     */
    private function getDamageOwnerDetails(Request $request, Damage $damage, ?UserIdentity $user, string $currentUserRole): array
    {
        $responsible = [];
        $role = (null !== $damage->getDamageOwner()) ? $this->getRevelantRole($request, $damage, $damage->getDamageOwner()) : null;

        if (!is_null($role)) {
            if ($damage->getAllocation() === true) {
                $responsible[] = [
                    'publicId' => $damage->getDamageOwner()->getPublicId(),
                    'firstName' => $damage->getDamageOwner()->getFirstName(),
                    'lastName' => $damage->getDamageOwner()->getLastName(),
                    'role' => $this->camelCaseConverter($role),
                ];
            }

            $responsible[] = [
                'publicId' => $damage->getUser()->getPublicId(),
                'firstName' => $damage->getUser()->getFirstName(),
                'lastName' => $damage->getUser()->getLastName(),
                'role' => $this->getRevelantRole($request, $damage, $damage->getUser()),
            ];

            if ($damage->getApartment()->getProperty()->getAdministrator() instanceof UserIdentity &&
                $damage->getApartment()->getProperty()->getUser()->getIdentifier() === $damage->getDamageOwner()->getIdentifier()) {
                $responsible[] = [
                    'publicId' => $damage->getApartment()->getProperty()->getAdministrator()->getPublicId(),
                    'firstName' => $damage->getApartment()->getProperty()->getAdministrator()->getFirstName(),
                    'lastName' => $damage->getApartment()->getProperty()->getAdministrator()->getLastName(),
                    'role' => $this->camelCaseConverter(Constants::PROPERTY_ADMIN_ROLE),
                ];
            }
        }

        usort($responsible, function ($a, $b) {
            return ($a['publicId'] <=> $b['publicId']) || ($a['role'] <=> $b['role']);
        });

        $uniqueArray = array_map("unserialize", array_unique(array_map("serialize", $responsible)));
        $uniqueArray = array_values($uniqueArray);

        return $uniqueArray;
    }

    /**
     * Get the message ID for the given damage.
     *
     * @param Damage $damage The Damage entity
     *
     * @return string|null The message ID, or null if no message is found
     */
    private function getMessageId(Damage $damage): ?string
    {
        $messageThread = $this->em->getRepository(Message::class)->findOneBy(['damage' => $damage, 'deleted' => false]);
        return $messageThread ? $messageThread->getPublicId() : null;
    }

    /**
     * Get the property address details for the given property.
     *
     * @param Property $property The Property entity
     *
     * @return array The property address details
     */
    private function getPropertyAddress(Property $property): array
    {
        $address = [];
        foreach (['streetName', 'streetNumber', 'postalCode', 'city', 'state', 'country', 'countryCode', 'latitude', 'longitude'] as $key) {
            $getter = 'get' . ucfirst($key);
            $address[$key] = $property->$getter();
        }
        return $address;
    }

    /**
     * Get the janitor details for the given property.
     *
     * @param Property $property The Property entity
     *
     * @return array|null The janitor details, or null if no janitor is found
     */
    private function getJanitorDetails(Property $property): ?array
    {
        if ($property->getJanitor() instanceof UserIdentity) {
            return $this->userService->getFormattedData($property->getJanitor(), true);
        }
        return null;
    }

    /**
     * Get the admin details for the given property.
     *
     * @param Property $property The Property entity
     *
     * @return array|null The admin details, or null if no admin is found
     */
    private function getAdminDetails(Property $property): ?array
    {
        if ($property->getAdministrator() instanceof UserIdentity) {
            return $this->userService->getFormattedData($property->getAdministrator(), true);
        }
        return null;
    }

    /**
     * Get the company details for the given damage.
     *
     * @param Damage $damage The Damage entity
     *
     * @return array|null The company details, or null if no company is found
     */
    private function getCompanyDetails(Damage $damage): ?array
    {
        if ($damage->getDamageOffers()) {
            $offerAcceptedCompany = $this->getOfferAcceptedCompany($damage->getDamageOffers());
            if (!is_null($offerAcceptedCompany)) {
                $companyDetails = $this->userService->getFormattedData($offerAcceptedCompany, true);
                $companyDetails['companyUsers'] = $this->em->getRepository(UserIdentity::class)->getActiveCompanyUsers($offerAcceptedCompany);
                return $companyDetails;
            }
        }
        return null;
    }

    /**
     * Get the parent company details for the given damage.
     *
     * @param Damage $damage The Damage entity
     *
     * @return array|null The parent company details, or null if no parent company is found
     */
    private function getParentCompanyDetails(Damage $damage): ?array
    {
        if ($damage->getDamageOffers()) {
            $offerAcceptedCompany = $this->getOfferAcceptedCompany($damage->getDamageOffers());
            if (!is_null($offerAcceptedCompany)) {
                if ($offerAcceptedCompany->getParent()) {
                    $parentCompanyDetails = $this->userService->getFormattedData($offerAcceptedCompany->getParent(), true);
                    $parentCompanyDetails['companyUsers'] = $this->em->getRepository(UserIdentity::class)->getActiveCompanyUsers($offerAcceptedCompany->getParent());
                    return $parentCompanyDetails;
                } else {
                    $companyDetails = $this->userService->getFormattedData($offerAcceptedCompany, true);
                    $companyDetails['companyUsers'] = $this->em->getRepository(UserIdentity::class)->getActiveCompanyUsers($offerAcceptedCompany);
                    return $companyDetails;
                }
            }
        }
        return null;
    }

    /**
     * Get the offer public ID for the given damage.
     *
     * @param Damage $damage The Damage entity
     *
     * @return string|null The offer public ID, or null if no offer is found
     */
    private function getOfferPublicId(Damage $damage): ?string
    {
        $offer = $this->em->getRepository(DamageOffer::class)->findOneBy(['damage' => $damage, 'deleted' => 0, 'active' => 1]);
        return $offer ? $offer->getPublicId() : null;
    }

    /**
     * Get the appointment details for the given damage.
     *
     * @param Damage $damage The Damage entity
     *
     * @return array The appointment details
     */
    private function getAppointmentDetails(Damage $damage): array
    {
        $appointment = $this->em->getRepository(DamageAppointment::class)->findOneBy(['damage' => $damage, 'deleted' => 0, 'status' => 1], ['createdAt' => 'DESC']);
        return [
            'publicId' => $appointment ? $appointment->getPublicId() : null,
            'scheduledTime' => $appointment ? $appointment->getScheduledTime() : null,
        ];
    }

    /**
     * Get the active comment details for the given damage.
     *
     * @param Damage $damage The Damage entity
     *
     * @return array|null The active comment details, or null if no active comment is found
     */
    private function getActiveCommentDetails(Damage $damage): ?array
    {
        $comment = $this->em->getRepository(DamageComment::class)->findOneBy(['damage' => $damage, 'deleted' => 0, 'currentActive' => true]);
        if ($comment) {
            return [
                'comment' => $comment->getComment(),
                'status' => $comment->getStatus()->getKey(),
                'createdAt' => $comment->getCreatedAt(),
            ];
        }
        return null;
    }

    /**
     * Get the confirmation date for the given damage.
     *
     * @param Damage $damage The Damage entity
     *
     * @return DateTimeInterface|null The confirmation date, or null if no confirmation date is found
     */
    private function getConfirmationDate(Damage $damage): ?DateTimeInterface
    {
        $confirmationStatusCode = $this->em->getRepository(DamageStatus::class)->findOneBy(['key' => Constants::STATUS['confirm_repair_status']]);
        $damageLog = $this->em->getRepository(DamageLog::class)->findOneBy(['damage' => $damage, 'status' => $confirmationStatusCode]);
        return $damageLog ? $damageLog->getCreatedAt() : null;
    }

    /**
     * @param array $damageDetail
     * @param Damage $damage
     * @return array
     */
    public function updateTenantInfoInDamageDetails(array &$damageDetail, Damage $damage): array
    {
        $role = $this->em->getRepository(Role::class)->findBy(['roleKey' => Constants::TENANT_ROLE]);
        $tenants = $this->em->getRepository(PropertyUser::class)->findBy(['object' => $damage->getApartment(), 'deleted' => 0, 'role' => $role, 'isActive' => 1]);
        foreach ($tenants as $tenant) {
            $damageDetail['tenants'][] = $this->userService->getFormattedData($tenant->getUser(), true);
        }
        $role = $this->em->getRepository(Role::class)->findBy(['roleKey' => $this->camelCaseConverter(Constants::OBJECT_OWNER_ROLE)]);
        $objectOwners = $this->em->getRepository(PropertyUser::class)->findBy(['object' => $damage->getApartment(), 'deleted' => 0, 'role' => $role, 'isActive' => 1]);
        foreach ($objectOwners as $objectOwner) {
            $damageDetail['objectOwner'][] = $this->userService->getFormattedData($objectOwner->getUser(), true);
        }
        if ($damage->getApartment()->getProperty()->getJanitor() instanceof UserIdentity) {
            $damageDetail['janitor'] = $this->userService->getFormattedData($damage->getApartment()->getProperty()->getJanitor(), true);
        }

        return $damageDetail;
    }

    /**
     * Get the availability of the chat option for a given damage, user, and current user role.
     *
     * @param Damage $damage The Damage entity for which to check the chat option availability
     * @param UserIdentity $user The current user identity
     * @param string $currentUserRole The current user's role
     * @param bool $countOnly Whether to only return the count of unique users involved or the boolean availability
     *
     * @return bool|int If $countOnly is true, returns the count of unique users involved. Otherwise, returns a boolean indicating the chat option availability
     */
    public function getChatOptionAvailability(Damage $damage, UserIdentity $user, string $currentUserRole, bool $countOnly = false)
    {
        $companyIds = [];
        foreach ($damage->getDamageOffers() as $offer) {
            if (!is_null($offer->getAcceptedDate())) {
                $companyIds[] = $offer->getCompany()->getIdentifier();
            }
        }

        if (($currentUserRole == Constants::COMPANY_ROLE) && !(in_array($user->getIdentifier(), $companyIds))) {
            return false;
        }

        if ($this->snakeToCamelCaseConverter($currentUserRole) == Constants::COMPANY_USER_ROLE
            && !in_array($user->getParent()->getIdentifier(), $companyIds)) {
            return false;
        }

        $damageOwnerArray = $damage->getDamageOwner()->getIdentifier();
        if (in_array($damage->getCreatedByRole()->getRoleKey(), Constants::LOG_REPAIR_CONFIRM_TENANT_WORKFLOW) &&
            in_array($damage->getStatus()->getKey(), Constants::CHAT_DISABLE_DAMAGE_STATUS)) {
            $damageOwnerArray = null;
        }

        $users = array_merge(
            $companyIds,
            is_null($damageOwnerArray) ? [] : [$damageOwnerArray],
            [$damage->getUser()->getIdentifier()]
        );

        if ($countOnly) {
            return count(array_unique($users));
        }

        return count(array_unique($users)) > 1;
    }

    /**
     * @param Property $property
     * @return array
     */
    public function formatPropertyDetails(Property $property): array
    {
        return [
            'publicId' => $property->getPublicId(),
            'address' => $property->getAddress(),
            'latitude' => $property->getLatitude(),
            'longitude' => $property->getLongitude(),
            'isActive' => $property->getActive(),
            'isPropertyActive' => $property->getActive(),
            'streetName' => $property->getStreetName(),
            'streetNumber' => $property->getStreetNumber(),
            'city' => $property->getCity(),
            'currency' => $property->getCurrency(),
            'countryCode' => $property->getCountryCode(),
            'country' => $property->getCountry(),
            'postalCode' => $property->getPostalCode()
        ];
    }

    /**
     * @param Collection $offers
     * @return string|null
     */
    public function getCompanyName(Collection $offers): ?string
    {
        $company = null;
        $offerAcceptedCompany = $this->getOfferAcceptedCompany($offers);
        if (!is_null($offerAcceptedCompany)) {
            if ($offerAcceptedCompany->getParent()) {
                $company = $offerAcceptedCompany->getParent()->getCompanyName();
            } else {
                $company = $offerAcceptedCompany->getCompanyName();
            }
        }

        return $company;
    }

    /**
     * @param Damage $damage
     * @param Request $request
     * @param UserIdentity|null $requestedUser
     * @return array
     * @throws \Exception
     */
    public function getFormattedDamageRequests(Damage $damage, Request $request, ?UserIdentity $requestedUser = null): array
    {
        $damageRequests = $this->em->getRepository(Damage::class)->getAllOfferAndRequests($damage->getIdentifier(), $requestedUser);
        $arrayResult = [];
        $requestCount = $offerCount = 0;
        if (!empty($damageRequests)) {
            foreach ($damageRequests as $result) {
                $requestCount++;
                (isset($result['offer']) && !empty($result['offer']) &&
                    !is_null($result['amount'])) ? $offerCount++ : '';
                $data['requestPublicId'] = $result['request'];
                $data['companyId'] = $result['company'];
                $data['offerId'] = $result['offer'];
                $data['firstName'] = $result['firstName'];
                $data['lastName'] = $result['lastName'];
                $data['email'] = $result['email'];
                $data['phone'] = $result['phone'];
                $data['landLine'] = $result['landLine'];
                $data['street'] = $result['street'];
                $data['streetNumber'] = $result['streetNumber'];
                $data['city'] = $result['city'];
                $data['zipCode'] = $result['zipCode'];
                $data['state'] = $result['state'];
                $data['country'] = $result['country'];
                $data['countryCode'] = $result['countryCode'];
                $data['comment'] = $result['comment'];
                if (!is_null($result['attachment'])) {
                    $this->getDamageOfferImages($request, $result['attachment'], $data, false);
                }
                $data['phone'] = $result['phone'];
                $data['companyName'] = $result['companyName'];
                $data['accepted'] = $result['accepted'];
                $data['offerDescription'] = $result['offerDescription'];
                $data['amount'] = $result['amount'] ? number_format((float)($result['amount']), 2, '.', '') : null;;
                $data['status'] = !empty($result['status']) ? $this->em->getRepository(DamageStatus::class)
                    ->findOneBy(['identifier' => $result['status']])->getKey() : null;
                $data['priceSplit'] = (isset($result['priceSplit']) && !empty($result['priceSplit'])) ?
                    $result['priceSplit'] : null;
                array_push($arrayResult, $data);
                unset($data);
            }

            $arrayResult['offerRequestCount'] = ['requestCount' => $requestCount, 'offerCount' => $offerCount];

        }
        return $arrayResult;
    }

    /**
     * Based on apartment object get all non-edited floor planes
     *
     * @param Apartment $apartment
     * @param Request $request
     * @param bool|null $encodedData
     * @return array|null
     * @throws \Exception
     */
    public function getOriginalFloorPlanImages(Apartment $apartment, Request $request, ?bool $encodedData = true): ?array
    {
        $floorPlans = $this->objectService->getFloorPlan($apartment, $request, $encodedData);
        return $floorPlans['floorPlan'] ?? [];
    }

    /**
     * generateDefectDetails
     *
     * function to generate ticket details array
     *
     * @param Damage $damage
     * @param Request $request
     * @return array
     *
     * @throws \Exception
     */
    private function generateDefectDetails(Damage $damage, Request $request): array
    {
        $return = [];
        $defects = $this->em->getRepository(DamageDefect::class)->findBy(['damage' => $damage, 'deleted' => 0], ['createdAt' => 'DESC']);
        foreach ($defects as $defect) {
            $defectData['defectNumber'] = '#' . $defect->getIdentifier();
            $defectData['publicId'] = $defect->getPublicId();
            $defectData['title'] = $defect->getTitle();
            $defectData['description'] = $defect->getDescription();
            $defectData['createdAt'] = $defect->getCreatedAt();
            $images = $this->em->getRepository(DamageImage::class)->findBy(['damage' => $damage, 'deleted' => 0, 'imageCategory' => $this->parameterBag->get('image_category')['defect']]);
            foreach ($images as $image) {
                $defectData['attachment'][] = $this->fileUploadHelper->getDamageFileInfo($image, $request->getSchemeAndHttpHost());
            }
            $return[] = $defectData;
        }

        return $return;
    }

    /**
     * processImages
     *
     * function to process damage images
     *
     * @param Request $request
     * @param Damage $damage
     * @param bool $isEdit
     * @param UserIdentity $user
     * @return void
     * @throws \Exception
     */
    public function processImages(Request $request, Damage $damage, UserIdentity $user, bool $isEdit = false): void
    {
        $damageImages = $request->request->get('damageImages');
        $locationImage = $request->request->get('locationImage');
        $barCodeImage = $request->request->get('barCodeImage');
        $this->persistImages($damage, $user, $damageImages, false, $this->parameterBag->get('image_category')['photos']);
        if (!empty($locationImage)) {
            foreach ($locationImage as $floorPlan) {
                $this->persistImages($damage, $user, [$floorPlan['documentId']], true,
                    $this->parameterBag->get('image_category')['floor_plan'], $floorPlan['isFloorPlanEdit']);
            }
        }
        if (!empty($barCodeImage)) {
            $this->persistImages($damage, $user, [$barCodeImage], false, $this->parameterBag->get('image_category')['bar_code']);
        }
    }

    /**
     * persistImages
     *
     * function to store ticket images into table
     *
     * @param Damage $damage
     * @param UserIdentity $user
     * @param array|null $damageImages
     * @param bool $editable
     * @param int|null $imageCategory
     * @param bool|null $isFloorEdit
     * @param bool|null $isCreateOffer
     * @return void
     * @throws \PhpZip\Exception\ZipException
     * @throws \Exception
     */
    public function persistImages(Damage $damage, UserIdentity $user, ?array $damageImages = [],
                                  bool $editable = false, ?int $imageCategory = 1, ?bool $isFloorEdit = true,
                                  ?bool $isCreateOffer = false): void
    {
        if (!empty($damageImages)) {
            $folderInfo = $this->fileUploadHelper->getTicketDocFolder($damage, $user);
            $destinationFolder = $folderInfo['destinationFolder'];
            foreach ($damageImages as $img) {
                if (false === $isFloorEdit) {
                    $imageDetail = $this->em->getRepository(Document::class)->findOneBy(['publicId' => $img]);
                } else {
                    $imageDetail = $this->em->getRepository(TemporaryUpload::class)->findOneBy(['publicId' => $img]);
                }
                if (null !== $imageDetail) {
                    if ($imageDetail instanceof TemporaryUpload) {
                        $size = $imageDetail->getFileSize();
                        $displayName = $imageDetail->getOriginalFileName();
                        $tempPath = $imageDetail->getTemporaryUploadPath();
                        $localName = $originalName = $imageDetail->getLocalFileName();
                    } else {
                        $size = $imageDetail->getSize();
                        $localName = $originalName = $imageDetail->getOriginalName();
                        $tempPath = $imageDetail->getStoredPath();
                        $displayName = $imageDetail->getDisplayName();
                    }
                    $destinationPath = $destinationFolder->getPath() . '/' . $originalName;
                    $damageImage = new DamageImage();
                    $this->containerUtility->convertRequestKeysToSetters([
                        'damage' => $damage,
                        'name' => $localName,
                        'displayName' => $displayName,
                        'isEditable' => $editable,
                        'path' => $destinationPath,
                        'imageCategory' => $imageCategory,
                        'mimeType' => $imageDetail->getMimeType(),
                        'fileSize' => $size,
                        'folder' => $destinationFolder
                    ], $damageImage);
                    if ($imageDetail instanceof TemporaryUpload) {
                        rename($tempPath, $destinationPath);
                        $this->em->remove($imageDetail);
                    } else {
                        copy($tempPath, $destinationPath);
                    }
                    $this->fileUploaderUtility->optimizeFile($destinationPath, $imageDetail->getMimeType());
                    $this->em->persist($damageImage);
                    if ($isCreateOffer) {
                        $damageOffer = $this->em->getRepository(DamageOffer::class)
                            ->findOneBy(['damage' => $damage, 'company' => $user]);
                        if ($damageOffer instanceof DamageOffer) {
                            $damageOffer->setAttachment($damageImage);
                        }
                    }
                }
            }
            $this->em->flush();
        }
    }

    /**
     * saving offer images
     *
     * @param Damage $damage
     * @param UserIdentity $user
     * @param int|null $imageCategory
     * @param array|null $damageImages
     * @param DamageOffer $damageOffer
     * @throws \PhpZip\Exception\ZipException
     * @throws \Exception
     */
    public function persistOfferImages(Damage $damage, UserIdentity $user, DamageOffer $damageOffer, ?int $imageCategory,
                                       ?array $damageImages = []): void
    {
        if (!empty($damageImages)) {
            $folderInfo = $this->fileUploadHelper->getTicketDocFolder($damage, $user);
            $destinationFolder = $folderInfo['destinationFolder'];
            foreach ($damageImages as $img) {
                $imageDetail = $this->em->getRepository(TemporaryUpload::class)->findOneBy(['publicId' => $img]);
                if ($imageDetail instanceof TemporaryUpload) {
                    $size = $imageDetail->getFileSize();
                    $displayName = $originalName = $imageDetail->getOriginalFileName();
                    $tempPath = $imageDetail->getTemporaryUploadPath();
                    $localName = $imageDetail->getLocalFileName();
                    $destinationPath = $destinationFolder->getPath() . '/' . $originalName;
                    $damageImage = new DamageImage();
                    $this->containerUtility->convertRequestKeysToSetters([
                        'damage' => $damage,
                        'name' => $localName,
                        'displayName' => $displayName,
                        'isEditable' => false,
                        'path' => $destinationPath,
                        'imageCategory' => $imageCategory,
                        'mimeType' => $imageDetail->getMimeType(),
                        'fileSize' => $size,
                        'folder' => $destinationFolder
                    ], $damageImage);
                    rename($tempPath, $destinationPath);
                    $this->em->remove($imageDetail);
                    $this->fileUploaderUtility->optimizeFile($destinationPath, $imageDetail->getMimeType());
                    $this->em->persist($damageImage);
                    if ($damageOffer instanceof DamageOffer) {
                        $damageOffer->setAttachment($damageImage);
                    }
                }
            }
            $this->em->flush();
        }
    }

    /**
     *
     * @param Request $request
     * @param UserIdentity $user
     * @param string $currentUserRole
     * @return array
     * @throws \Exception
     */
    public function getDashboardTickets(Request $request, UserIdentity $user, string $currentUserRole): array
    {
        return $this->getDamageList($request, $user, $currentUserRole, false, true);
    }

    /**
     * getDamageList
     *
     * function to get list of damage tickets
     *
     * @param Request $request
     * @param UserIdentity $user
     * @param string $currentRole
     * @param bool $count
     * @param bool $dashboard
     * @return array
     * @throws \Exception
     */
    public function getDamageList(Request $request, UserIdentity $user, string $currentRole, bool $count = false, bool $dashboard = false): array
    {
        $damages = $this->em->getRepository(Damage::class)->getAllDamages($user, $currentRole, $this->getDamageFilters($request), $count, $dashboard);
        return $this->getFormattedDamageList($damages, $request, $user, $currentRole);
    }

    /**
     * getDamageFilters
     *
     * function to get list of damage filters
     *
     * @param Request $request
     * @return array
     */
    public function getDamageFilters(Request $request): array
    {
        $param['offset'] = $request->get('offset');
        $param['limit'] = $request->get('limit');
        $param['type'] = $request->get('type');
        $param['apartment'] = false;
        $param['property'] = false;
        $filters = $request->get('filter');
        if (null !== $filters) {
            foreach ($filters as $key => $filter) {
                if ($key === 'apartment') {
                    $param['apartment'] = $this->em->getRepository(Apartment::class)->findOneBy(['deleted' => false, 'publicId' => $filter]);
                } elseif ($key === 'property') {
                    $param['property'] = $this->em->getRepository(Property::class)->findOneBy(['deleted' => false, 'publicId' => $filter]);
                } elseif ($key === 'status' && ($filter === 'closed' || $filter === 'open')) {
                    $param['status'] = $filter;
                } else {
                    $param[$key] = $filter;
                }
            }
        }

        return $param;
    }

    /**
     * Update the status of a damage.
     *
     * @param Request $request
     * @param UserIdentity $user
     * @param string $userRole
     * @param string|null $statusString
     * @param bool $statusUpdate
     * @return string
     * @throws AccessDeniedException
     */
    public function updateStatus(Request $request, UserIdentity $user, string $userRole, ?string $statusString = null, $statusUpdate = true): string
    {
        $em = $this->em;
        $damage = $this->validateAndGetDamageObject($request->request->get('ticket'));

        $this->validateCurrentStatus($damage, $request->request->get('currentStatus'));

        $status = $this->validateAndGetStatus((null !== $statusString) ? $statusString : $request->request->get('status'));
        $statusKey = $status->getKey();
        $currentRole = $this->getCurrentRole($em, $userRole);

        $this->validateAndSetComment($request->request->get('comment'), $damage, $status);
        $this->validateAndSetCompany($request, $damage, $user, $currentRole);

        $this->setCompanyAssignedBy($request, $damage, $user, $currentRole);

        $damage->setUpdatedAt(new \DateTime('now'));

        if ($statusUpdate) {
            $this->updateDamageStatus($request, $damage, $status, $em, $user);
        }

        $this->setIssueType($request, $damage, $em);
        $this->setAllocation($request, $damage);

        $em->persist($damage);
        $this->saveDamageAppointment($damage, $request, $user);
        $this->saveDamageDefect($damage, $request, $user);

        $this->setSignature($statusKey, $damage, $request);
        $this->setDamageOwner($statusKey, $damage, $user);
        $this->setAcceptedDate($statusKey, $request, $em);

        $this->updateDamageRequest($request, $em, $userRole);

        $this->updateDamageStatusBasedOnRequests($damage, $em, $user);

        $this->setCurrentUser($damage, $statusKey);
        $em->flush();

        if (strpos($statusKey, 'PROPERTY_ADMIN') !== false) {
            $statusKey = str_replace('PROPERTY_ADMIN', 'OWNER', $statusKey);
        }

        return $statusKey;
    }

    /**
     * Validate the current status of the damage.
     *
     * @param mixed $damage
     * @param string $currentStatus
     * @throws AccessDeniedException
     */
    private function validateCurrentStatus($damage, string $currentStatus): void
    {
        if ($damage->getStatus()->getKey() !== $currentStatus) {
            throw new AccessDeniedException('formExpired');
        }
    }

    /**
     * Get the current role.
     *
     * @param EntityManagerInterface $em
     * @param string $userRole
     * @return Role|null
     */
    private function getCurrentRole(EntityManagerInterface $em, string $userRole): ?Role
    {
        return $em->getRepository(Role::class)->findOneBy(['roleKey' => $userRole]);
    }

    /**
     * Set the company assigned by for the damage.
     *
     * @param Request $request
     * @param mixed $damage
     * @param UserIdentity $user
     * @param Role|null $currentRole
     */
    private function setCompanyAssignedBy(Request $request, $damage, UserIdentity $user, ?Role $currentRole): void
    {
        if (in_array($request->request->get('status'), Constants::LOG_DAMAGE_ACCEPT)) {
            $damage->setCompanyAssignedBy($user);
            $damage->setCompanyAssignedByRole($currentRole);
        }
    }

    /**
     * Update the damage status.
     *
     * @param Request $request
     * @param mixed $damage
     * @param mixed $status
     * @param EntityManagerInterface $em
     * @param UserIdentity $user
     * @throws \Exception
     */
    private function updateDamageStatus(Request $request, $damage, $status, EntityManagerInterface $em, UserIdentity $user): void
    {
        $damage->setStatus($status);
        $damageOffer = null;

        if (!empty($request->get('offer'))) {
            $damageOffer = $em->getRepository(DamageOffer::class)->findOneBy(['publicId' => $request->get('offer')]);
        }

        if (!in_array($request->get('status'), Constants::LOG_DAMAGE_COMPANY_SCHEDULE_DATE)) {
            $this->logDamage($user, $damage, null, null, $damageOffer);
        }
    }

    /**
     * Set the issue type for the damage.
     *
     * @param Request $request
     * @param mixed $damage
     * @param EntityManagerInterface $em
     */
    private function setIssueType(Request $request, $damage, EntityManagerInterface $em): void
    {
        if ($request->request->get('issueType') != '') {
            $issueType = $em->getRepository(Category::class)->findOneBy(['publicId' => $request->request->get('issueType')]);
            $damage->setIssueType($issueType);
        }
    }

    /**
     * Set the allocation for the damage.
     *
     * @param Request $request
     * @param mixed $damage
     */
    private function setAllocation(Request $request, $damage): void
    {
        if ($request->request->has('allocationType')) {
            $damage->setAllocation($request->request->get('allocationType'));
        }
    }

    /**
     * Set the signature for the damage.
     *
     * @param string $statusKey
     * @param mixed $damage
     * @param Request $request
     */
    private function setSignature(string $statusKey, $damage, Request $request): void
    {
        if ($statusKey === 'REPAIR_CONFIRMED') {
            $damage->setSignature($request->request->get('withSignature') ? true : false);
        }
    }

    /**
     * Set the damage owner.
     *
     * @param string $statusKey
     * @param mixed $damage
     * @param UserIdentity $user
     */
    private function setDamageOwner(string $statusKey, $damage, UserIdentity $user): void
    {
        if (in_array($statusKey, Constants::REJECTED_DAMAGE_BY_OWNER_OR_ADMIN)) {
            $damage->setDamageOwner($user);
        }
    }

    /**
     * Set the accepted date for the damage offer.
     *
     * @param string $statusKey
     * @param Request $request
     * @param EntityManagerInterface $em
     */
    private function setAcceptedDate(string $statusKey, Request $request, EntityManagerInterface $em): void
    {
        if (in_array($statusKey, Constants::ACCEPT_DAMAGES)) {
            $offer = $em->getRepository(DamageOffer::class)->findOneBy(['publicId' => $request->get('offer')]);
            if ($offer instanceof DamageOffer) {
                $offer->setAcceptedDate(new \DateTime('now'));
                $em->flush();
            }
        }
    }

    /**
     * Update the damage request.
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param string $userRole
     */
    private function updateDamageRequest(Request $request, EntityManagerInterface $em, string $userRole): void
    {
        if ($request->get('damageRequest')) {
            $requestedStatus = $request->request->get('damageRequestStatus');
            $requestStatus = $this->validateAndGetStatus($requestedStatus);
            $damageRequest = $em->getRepository(DamageRequest::class)->findOneBy(['publicId' => $request->get('damageRequest')]);

            if ($damageRequest instanceof DamageRequest) {
                $damageRequest->setStatus($requestStatus);

                if ($requestedStatus === Constants::COMPANY_REJECT_THE_DAMAGE) {
                    $damageRequest->setRequestRejectDate(new \DateTime());
                }

                $em->flush();

                if (in_array($request->get('damageRequestStatus'), Constants::ACCEPT_DAMAGES)) {
                    $requestCloseStatus = $this->validateAndGetStatus(strtoupper($userRole) . '_CLOSE_THE_DAMAGE');
                    $em->getRepository(DamageRequest::class)->updateDamageRequestStatusToClose($damageRequest, $requestCloseStatus);
                }
            }
        }
    }

    /**
     * Update the damage status based on the damage requests.
     *
     * @param mixed $damage
     * @param EntityManagerInterface $em
     * @param UserIdentity $user
     * @throws \Exception
     */
    private function updateDamageStatusBasedOnRequests($damage, EntityManagerInterface $em, UserIdentity $user): void
    {
        if ($damage) {
            $requestRejectedStatus = $this->validateAndGetStatus(Constants::COMPANY_REJECT_THE_DAMAGE);
            $damageRequests = $em->getRepository(DamageRequest::class)->findBy(['damage' => $damage]);
            $damageRequestsRejected = $em->getRepository(DamageRequest::class)->findBy(['damage' => $damage, 'status' => $requestRejectedStatus]);

            if ($damageRequests instanceof DamageRequest && count($damageRequests) == count($damageRequestsRejected)) {
                $damage->setStatus($requestRejectedStatus);
                $this->logDamage($user, $damage);
            }
        }
    }

    /**
     * saveDamageAppointment
     *
     * function to save Damage Appointment
     *
     * @param Damage $damage
     * @param Request $request
     * @param UserIdentity $user
     * @return void
     * @throws \Exception
     */
    private function saveDamageAppointment(Damage $damage, Request $request, UserIdentity $user): void
    {
        if ($request->get('status') === 'COMPANY_SCHEDULE_DATE') {
            $damageAppointment = new DamageAppointment();
            $damageAppointment->setDamage($damage);
            $damageAppointment->setStatus(true);
            $damageAppointment->setUser($user);
            $damageAppointment->setScheduledTime(\DateTime::createFromFormat('Y-m-d H:i', $request->request->get('date') . ' ' . $request->request->get('time')));
            $this->em->persist($damageAppointment);
            $this->em->flush();
            $this->logDamage($user, $damage);
        }
    }

    /**
     * saveDamageAppointment
     *
     * function to save Damage Appointment
     *
     * @param Damage $damage
     * @param Request $request
     * @param UserIdentity $user
     * @return void
     * @throws
     */
    private function saveDamageDefect(Damage $damage, Request $request, UserIdentity $user): void
    {
        if ($request->get('status') === 'DEFECT_RAISED') {
            $damageDefect = new DamageDefect();
            $damageDefect->setDamage($damage);
            $damageDefect->setTitle($request->request->get('title'));
            $damageDefect->setDescription($request->request->get('description'));
            $damageDefect->setUser($user);
            $this->persistImages($damage, $user, $request->request->get('attachment'), false, $this->parameterBag->get('image_category')['defect']);
            $this->em->persist($damageDefect);
            $this->em->flush();
        }

        return;
    }

    /**
     * sendDamageEmail
     *
     * function to send Damage Email
     *
     * @param Request $request
     * @param Damage $damage
     * @param UserIdentity $user
     * @param string $currentRole
     * @return void
     */
    public function sendDamageEmail(Request $request, Damage $damage, UserIdentity $user, string $currentRole): void
    {
        $emailFunction = 'email' . str_replace(' ', '', ucwords(strtolower(str_replace('_', ' ', $damage->getStatus()->getKey()))));
        if (!strpos($damage->getStatus()->getKey(), 'CREATE')) {
            if (strpos($damage->getStatus()->getKey(), 'PROPERTY_ADMIN') !== false) {
                $emailFunction = str_replace('PropertyAdmin', 'Owner', $emailFunction);
            }
            if (strpos($damage->getStatus()->getKey(), 'JANITOR') !== false) {
                $emailFunction = str_replace('Janitor', 'Owner', $emailFunction);
            }
            if (strpos($damage->getStatus()->getKey(), 'OBJECT_OWNER') !== false) {
                $emailFunction = str_replace('ObjectOwner', 'Tenant', $emailFunction);
            }
        }
        if (method_exists(__CLASS__, $emailFunction)) {
            $this->$emailFunction($request, $damage, $emailFunction, $user, $currentRole);
        } else {
            $this->sendGenericDamageMail($request, $damage, $emailFunction, $user, $currentRole);
        }
    }

    /**
     * validateAndGetCompany
     *
     * function to get valid company
     *
     * @param string|null $comment
     * @param Damage $damage
     * @param DamageStatus|null $status
     * @return bool
     */
    public function validateAndSetComment(?string $comment, Damage $damage, ?DamageStatus $status = null): bool
    {
        if (null === $status) {
            $status = $damage->getStatus();
        }
        if (($status->getCommentRequired()) && (is_null($comment))) {
            throw new AccessDeniedException('commentRequired');
        }
        if (!is_null($comment)) {
            $activeDamageComment = $this->em->getRepository(DamageComment::class)->findOneBy(['damage' => $damage, 'currentActive' => 1]);
            if ($activeDamageComment instanceof DamageComment) {
                $activeDamageComment->setCurrentActive(0);
                $this->em->persist($activeDamageComment);
            }
            $damageComment = new DamageComment();
            $damageComment->setComment($comment);
            $damageComment->setStatus($status);
            $damageComment->setCurrentActive(1);
            $damageComment->setDamage($damage);
            $this->em->persist($damageComment);
        }

        return true;
    }

    /**
     * validateAndGetDamageObject
     *
     * function to validate and get damageObject
     *
     * @param string $damage
     * @return Damage
     */
    public function validateAndGetDamageObject(string $damage): Damage
    {
        $damage = $this->em->getRepository(Damage::class)->findOneBy(['publicId' => $damage, 'deleted' => 0]);
        if (!$damage instanceof Damage) {
            throw new ResourceNotFoundException("invalidData");
        }
        return $damage;
    }

    /**
     * validateAndGetStatus
     *
     * function to get valid status from key
     *
     * @param string $status
     * @return DamageStatus
     */
    public function validateAndGetStatus(string $status): DamageStatus
    {
        $status = $this->em->getRepository(DamageStatus::class)->findOneBy(['key' => $status]);
        if (!$status instanceof DamageStatus) {
            throw new ResourceNotFoundException("invalidDamageStatus");
        }

        return $status;
    }

    /**
     * validateAndSetCompany
     *
     * function to validate and set assigned company
     *
     * @param Request $request
     * @param Damage $damage
     * @param UserIdentity $user
     * @param Role $currentRole
     * @return bool
     */
    private function validateAndSetCompany(Request $request, Damage $damage, UserIdentity $user, Role $currentRole): bool
    {
        $company = $request->request->get('company');
        if ($this->snakeToCamelCaseConverter($currentRole->getRoleKey()) == Constants::COMPANY_USER_ROLE) {
            $companyUser = $user->getParent();
            $company = $companyUser->getPublicId();
        }
        $status = $request->request->get('status');
        $requestedCompanies = $this->getRequestedCompanies($damage);
        if (strpos($status, 'COMPANY_ACCEPTS_DAMAGE') !== false && !in_array($user->getIdentifier(), $requestedCompanies)) {
            throw new AccessDeniedException('noPermission');
        }
        if ($status === 'COMPANY_REJECT_THE_DAMAGE') {
            if (null !== $damage->getAssignedCompany()) {
                $damage->removeUser($damage->getAssignedCompany());
                $damage->setAssignedCompany(null);
            }
        } elseif (null !== $company) {
            $company = $this->em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $company, 'deleted' => 0, 'isBlocked' => 0]);
            if (!$company instanceof UserIdentity) {
                throw new ResourceNotFoundException("invalidCompany");
            }
            if ($damage->getAssignedCompany() !== $company && $company !== $user) {
                if (null !== $damage->getAssignedCompany()) {
                    $damage->removeUser($damage->getAssignedCompany());
                }
                $damage->setCompanyAssignedBy($user);
                $damage->setCompanyAssignedByRole($currentRole);
                $damage->setAssignedCompany($company);
                $damage->addUser($company);
            }
        }
        $this->em->persist($damage);

        return true;
    }

    /**
     * validateUserRole
     *
     * function to check given status against current user role
     *
     * @param string $status
     * @param UserIdentity $user
     * @param string $userRole
     * @param Property $property
     * @return string || throws Exception
     */
    private function validateAndGetUserRole(string $status, UserIdentity $user, string $userRole,
                                            Property $property): string
    {
        $currentUserRole = $this->userService->getCurrentUserRole($user, $userRole);
        foreach ($user->getRole() as $roleObj) {
            $statusArray = $this->em->getRepository(DamageStatus::class)->getDamageStatus($roleObj->getRoleKey());
            $role = $this->userService->getParentRole($roleObj->getRoleKey());
            $inherittedStatus = $this->em->getRepository(DamageStatus::class)->getDamageStatus($role);
            $statusArray = array_merge($statusArray, $inherittedStatus);
            $statusArray[] = Constants::STATUS['confirm_repair_status'];
            if ($currentUserRole != $this->parameterBag->get('user_roles')['company']) {
                $statusArray[] = Constants::STATUS['damage_status']['DEFECT_RAISED'];
            }
            if (/* $role === $currentUserRole && */ in_array($status, $statusArray) ||
                (($user->getIdentifier() == $property->getUser()->getIdentifier()) ||
                    (!is_null($property->getAdministrator()) && $user->getIdentifier() == $property->getAdministrator()->getIdentifier()))) {
                return $roleObj->getRoleKey();
            }
        }

        throw new AccessDeniedException('noPermission');
    }

    /**
     * validatePermission
     *
     * function to validate permission to create a ticket
     *
     * @param Request $request
     * @param string $currentUserRole
     * @param UserIdentity $user
     * @param Apartment|null $apartment
     * @return bool
     */
    public function validatePermission(Request $request, string $currentUserRole, UserIdentity $user, ?Apartment $apartment = null): bool
    {
        $apartment = (null === $apartment) ? $this->em->getRepository(Apartment::class)->findOneBy(['publicId' => $request->request->get('apartment')]) : $apartment;
        $allRoles = $this->parameterBag->get('user_roles');
        if ($currentUserRole === Constants::OWNER_ROLE && $apartment->getProperty()->getUser() !== $user) {
            throw new AccessDeniedException('noPermission');
        } elseif ($currentUserRole === $this->camelCaseConverter(Constants::PROPERTY_ADMIN_ROLE)) {
            $isPropertyAdmin = $this->userService->isPropertyAdmin($apartment->getProperty());
            if (!$isPropertyAdmin) {
                throw new AccessDeniedException('noPermission');
            }
        } elseif (in_array($currentUserRole, [$allRoles['janitor']])) {
            $property = $this->em->getRepository(Property::class)->findOneBy(['janitor' => $user, 'publicId' => $apartment->getProperty()->getPublicId()]);
            if (!($property instanceof Property)) {
                throw new AccessDeniedException('noPermission');
            }
        } elseif (in_array($currentUserRole, [$allRoles['tenant'], $allRoles['object_owner']])) {
            $propertyUser = $this->em->getRepository(PropertyUser::class)->checkIfUserHasActiveRole($apartment->getId(), $user->getId(), $currentUserRole);
            if (!($propertyUser instanceof PropertyUser)) {
                throw new AccessDeniedException('noPermission');
            }
        }

        return true;
    }

    /**
     * getInitialDamageStatus
     *
     * function to get damage status when we create a ticket
     *
     * @param Request $request
     * @param string $userRole
     * @param UserIdentity $user
     * @param bool|null $isEdit
     * @param string|null $damageStatus
     * @return string
     */
    public function getInitialDamageStatus(Request $request, string $userRole, UserIdentity $user, ?bool $isEdit = false, ?string $damageStatus = null): string
    {
        $currentUserRole = $this->userService->getCurrentUserRole($user, $userRole);
        if ($isEdit == true) {
            return $damageStatus;
        }
        //        $allRoles = $this->parameterBag->get('user_roles');
//        if (in_array($currentUserRole, [$allRoles['owner'], $allRoles['property_admin']]) || (in_array($currentUserRole, [$allRoles['janitor'], $allRoles['object_owner'], $allRoles['tenant']]) && $request->request->get('sendToCompany'))) {
//            $statusKey = $request->request->get('isOfferPreferred') ? strtoupper($currentUserRole) . '_SEND_TO_COMPANY_WITH_OFFER' : strtoupper($currentUserRole) . '_SEND_TO_COMPANY_WITHOUT_OFFER';
//        } else {
//            $statusKey = strtoupper($currentUserRole) . '_CREATE_DAMAGE';
//        }
//        $statusKey = strtoupper($currentUserRole) . '_CREATE_DAMAGE';

        return strtoupper($currentUserRole) . '_CREATE_DAMAGE';
    }

    /**
     * function to log damage
     *
     * @param UserIdentity $user
     * @param Damage $damage
     * @param UserIdentity|null $assignedCompany
     * @param UserIdentity|null $preferredCompany
     * @param DamageOffer|null $damageOffer
     * @param array|null $companies
     * @return bool
     * @throws \Exception
     */
    public function logDamage(UserIdentity $user, Damage $damage, ?UserIdentity $assignedCompany = null,
                              ?UserIdentity $preferredCompany = null, ?DamageOffer $damageOffer = null, ?array $companies = []): bool
    {
        $statusText = $responsible = [];
        $onlyNotify = false;
        $status = $damage->getStatus()->getKey();
        switch (true) {
            case ($damageOffer instanceof DamageOffer):
                if (in_array($status, Constants::LOG_DAMAGE_COMPANY_OFFER_ACCEPT)) {
                    $logData = $this->logOfferGivenFromCompany($damage, $user, $damageOffer);
                    $statusText = $logData['statusText'];
                    $responsible = $logData['responsibles'];
                    $preferredCompany = $logData['preferredCompany'] ?? null;
                    break;
                }
                if (in_array($status, Constants::LOG_DAMAGE_COMPANY_OFFER_ACCEPT_BY_PRIVATE)) {
                    $logData = $this->logOfferGivenFromCompanyToPrivate($damage, $user, $damageOffer);
                    $statusText = $logData['statusText'];
                    $responsible = $logData['responsibles'];
                    $preferredCompany = $logData['preferredCompany'] ?? null;
                    break;
                }
                $status = $damageOffer->getDamageRequest()->getStatus()->getKey();
                if (in_array($status, Constants::LOG_COMPANY_GIVE_OFFER)) {
                    $logData = $this->logCompanyOfferDetails($damage, $user);
                    $statusText = $logData['statusText'];
                    $responsible = $logData['responsibles'];
                    $preferredCompany = $logData['preferredCompany'] ?? null;
                }
                if (in_array($status, Constants::LOG_COMPANY_GIVE_OFFER_TO_PRIVATE)) {
                    $logData = $this->logCompanyOfferDetailsToPrivate($damage, $user);
                    $statusText = $logData['statusText'];
                    $responsible = $logData['responsibles'];
                    $preferredCompany = $logData['preferredCompany'] ?? null;
                }
                if (in_array($status, [Constants::COMPANY_ACCEPT_THE_DAMAGE])) {
                    $logData = $this->logOfferRequestAcceptedByCompany($damage, $user);
                    $statusText = $logData['statusText'];
                    $responsible = $logData['responsibles'];
                    $preferredCompany = $logData['preferredCompany'] ?? null;
                }
                break;
            case (in_array($status, Constants::LOG_DAMAGE_CREATE)):
                $logData = $this->logTicketCreateDetails($damage, $user);
                $statusText = $logData['statusText'];
                $responsible = $logData['responsibles'];
                break;
            case (in_array($status, Constants::LOG_DAMAGE_ACCEPT)):
                $logData = $this->logTicketAcceptDetails($damage, $user);
                $statusText = $logData['statusText'];
                $responsible = $logData['responsibles'];
                break;
            case (in_array($status, Constants::LOG_DAMAGE_SEND_TO_COMPANY)):
                $logData = $this->logTicketSendToCompanyWithoutOffer($damage, $user, $companies);
                $statusText = $logData['statusText'];
                $responsible = $logData['responsibles'];
                break;
            case (in_array($status, Constants::LOG_DAMAGE_COMPANY_SCHEDULE_DATE)):
                if ((null !== $damage->getCompanyAssignedByRole()) && in_array($damage->getCompanyAssignedByRole()->getRoleKey(), Constants::LOG_REPAIR_CONFIRM_ADMIN_WORKFLOW)) {
                    $logData = $this->logScheduledDateFromCompany($damage, $user);
                    $statusText = $logData['statusText'] ?? [];
                    $responsible = $logData['responsibles'] ?? [];
                    $preferredCompany = $logData['preferredCompany'] ?? null;
                }
                if ((null !== $damage->getCompanyAssignedByRole()) && in_array($damage->getCompanyAssignedByRole()->getRoleKey(), Constants::LOG_REPAIR_CONFIRM_TENANT_WORKFLOW)) {
                    $logData = $this->logScheduledDateFromCompanyToPrivate($damage, $user);
                    $statusText = $logData['statusText'] ?? [];
                    $responsible = $logData['responsibles'] ?? [];
                    $preferredCompany = $logData['preferredCompany'] ?? null;
                }
                break;
            case (in_array($status, Constants::LOG_DAMAGE_CLOSE)):
                if ((null == $damage->getCompanyAssignedByRole()) || in_array($damage->getCompanyAssignedByRole()->getRoleKey(), Constants::LOG_REPAIR_CONFIRM_ADMIN_WORKFLOW)) {
                    $logData = $this->logRepairConfirmedDetails($damage, $user);
                    if (empty($logData)) {
                        $onlyNotify = true;
                    } else {
                        $statusText = $logData['statusText'];
                        $responsible = $logData['responsibles'];
                        $preferredCompany = $logData['preferredCompany'] ?? null;
                    }
                    break;
                }
                if ((null == $damage->getCompanyAssignedByRole()) || in_array($damage->getCompanyAssignedByRole()->getRoleKey(), Constants::LOG_REPAIR_CONFIRM_TENANT_WORKFLOW)) {
                    $logData = $this->logRepairConfirmedDetailsByPrivate($damage, $user);
                    $statusText = $logData['statusText'];
                    $responsible = $logData['responsibles'];
                    $preferredCompany = $logData['preferredCompany'] ?? null;
                }
                break;
            case (in_array($status, Constants::LOG_DAMAGE_SEND_TO_COMPANY_BY_PRIVATE)):
                $logData = $this->logTicketSendToCompanyByPrivateWithoutOffer($damage, $user, $companies);
                $statusText = $logData['statusText'];
                $responsible = $logData['preferredCompany'] ?? null;
                break;
            case (in_array($status, Constants::LOG_DAMAGE_CREATE_BY_ADMIN_OR_OWNER_PRIVATE)):
                $logData = $this->logTicketCreateDetailsByOwnerOrAdminPrivate($damage, $user);
                $statusText = $logData['statusText'];
                $responsible = $logData['preferredCompany'] ?? null;
                break;
            case (in_array($status, Constants::LOG_DAMAGE_CLOSE_BY_ADMIN_OR_OWNER)):
                if ((null == $damage->getCompanyAssignedByRole()) || in_array($damage->getCompanyAssignedByRole()->getRoleKey(), Constants::LOG_REPAIR_CONFIRM_ADMIN_WORKFLOW)) {
                    $logData = $this->logRepairConfirmedDetails($damage, $user);
                    if (empty($logData)) {
                        $onlyNotify = true;
                    } else {
                        $statusText = $logData['statusText'];
                        $responsible = $logData['responsibles'];
                        $preferredCompany = $logData['preferredCompany'] ?? null;
                    }
                }
                break;
            case (in_array($status, Constants::LOG_DAMAGE_REPAIR_COMPLETED)):
                $logData = $this->logRepairCompletedDetails($damage, $user);
                $statusText = $logData['statusText'];
                $responsible = $logData['responsibles'];
                $preferredCompany = $logData['preferredCompany'] ?? null;
                break;
            case (in_array($status, Constants::LOG_DAMAGE_CONFIRM_ACCEPT_DATE)):
                $this->logAppointmentConfirmedDetails($damage, $user);
                $onlyNotify = true;
                break;
            case (in_array($status, Constants::LOG_DAMAGE_REJECTS_DATE)):
                $this->logAppointmentRejectDetails($damage, $user);
                $onlyNotify = true;
                break;
            case (in_array($status, Constants::LOG_DAMAGE_DEFECT_RAISED)):
                $logData = $this->logDefectRaisedDetails($damage, $user);
                $statusText = $logData['statusText'];
                $responsible = $logData['responsibles'];
                $preferredCompany = $logData['preferredCompany'] ?? null;
                break;
            default:
                break;
        }
        if ($onlyNotify == true) return true;

        if (!is_null($preferredCompany)) {
            $preferredCompany = $this->em->getRepository(UserIdentity::class)->findOneBy(['identifier' => $preferredCompany]);
        }
        $damageLog = new DamageLog();
        $this->containerUtility->convertRequestKeysToSetters([
            'damage' => $damage,
            'status' => $damage->getStatus(),
            'user' => $user,
            'createdAt' => new \DateTime(),
            'assignedCompany' => $assignedCompany,
            'preferredCompany' => $preferredCompany,
            'responsibles' => $responsible,
            'statusText' => $statusText
        ], $damageLog);

        return true;
    }

    /**
     * @param Damage $damage
     * @param UserIdentity $user
     * @return array
     */
    public function logTicketCreateDetails(Damage $damage, UserIdentity $user): array
    {
        $data = $responsible = [];
        if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                ->convertCamelCaseString(Constants::OBJECT_OWNER_ROLE)) {
            array_push($responsible, 'object_owner');
            if (is_null($damage->getUpdatedAt())) {
                $data['statusText']['object_owner'] = [
                    'en' => $this->translator->trans('ticketAddedBy', [], null, 'en') . ' ' . $this->getUserName($damage->getUser()),
                    'de' => $this->translator->trans('ticketAddedBy', [], null, 'de') . ' ' . $this->getUserName($damage->getUser())
                ];
            } else {
                $data['statusText']['object_owner'] = [
                    'en' => $this->translator->trans('ticketEditedBy', [], null, 'en') . ' ' . $this->getUserName($damage->getUser()),
                    'de' => $this->translator->trans('ticketEditedBy', [], null, 'de') . ' ' . $this->getUserName($damage->getUser())
                ];
            }
        } else if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                ->convertCamelCaseString(Constants::TENANT_ROLE)) {
            if (is_null($damage->getUpdatedAt())) {
                $data['statusText']['tenant'] = [
                    'en' => $this->translator->trans('ticketAddedBy', [], null, 'en') . ' ' . $this->getUserName($damage->getUser()),
                    'de' => $this->translator->trans('ticketAddedBy', [], null, 'de') . ' ' . $this->getUserName($damage->getUser())
                ];
            } else {
                $data['statusText']['tenant'] = [
                    'en' => $this->translator->trans('ticketEditedBy', [], null, 'en') . ' ' . $this->getUserName($damage->getUser()),
                    'de' => $this->translator->trans('ticketEditedBy', [], null, 'de') . ' ' . $this->getUserName($damage->getUser())
                ];
            }
            array_push($responsible, 'tenant');
        }
        if (!is_null($damage->getDamageOwner())) {
            if ($damage->getApartment()->getProperty()->getUser()) {
                if (is_null($damage->getUpdatedAt())) {
                    $data['statusText']['owner'] = [
                        'en' => $this->translator->trans('ticketAddedBy', [], null, 'en') . ' ' . $this->getUserName($damage->getUser()),
                        'de' => $this->translator->trans('ticketAddedBy', [], null, 'de') . ' ' . $this->getUserName($damage->getUser())
                    ];
                } else {
                    $data['statusText']['owner'] = [
                        'en' => $this->translator->trans('ticketEditedBy', [], null, 'en') . ' ' . $this->getUserName($damage->getUser()),
                        'de' => $this->translator->trans('ticketEditedBy', [], null, 'de') . ' ' . $this->getUserName($damage->getUser())
                    ];
                }
                if ($damage->getAllocation() == true) {
                    if (is_null($damage->getApartment()->getProperty()->getAdministrator()))
                        $this->sendNotificationToUser($damage, $data['statusText']['owner'], $user, 'owner', $damage->getApartment()->getProperty()->getUser(), 'ticketAddedSubject');
                }
                array_push($responsible, 'owner');
            }
            if ($damage->getApartment()->getProperty()->getAdministrator()) {
                if (is_null($damage->getUpdatedAt())) {
                    $data['statusText']['property_admin'] = [
                        'en' => $this->translator->trans('ticketAddedBy', [], null, 'en') . ' ' . $this->getUserName($damage->getUser()),
                        'de' => $this->translator->trans('ticketAddedBy', [], null, 'de') . ' ' . $this->getUserName($damage->getUser()),
                    ];
                } else {
                    $data['statusText']['property_admin'] = [
                        'en' => $this->translator->trans('ticketEditedBy', [], null, 'en') . ' ' . $this->getUserName($damage->getUser()),
                        'de' => $this->translator->trans('ticketEditedBy', [], null, 'de') . ' ' . $this->getUserName($damage->getUser()),
                    ];
                }
                array_push($responsible, 'property_admin');
                if ($damage->getApartment()->getProperty()->getUser() != $damage->getApartment()->getProperty()->getAdministrator() && $damage->getAllocation() == true) {
                    $this->sendNotificationToUser($damage, $data['statusText']['property_admin'], $user, 'property_admin', $damage->getApartment()->getProperty()->getAdministrator(), 'ticketAddedSubject');
                }
            }
            if ($damage->getApartment()->getProperty()->getJanitor()) {
                if (is_null($damage->getUpdatedAt())) {
                    $data['statusText']['janitor'] = [
                        'en' => $this->translator->trans('ticketAddedBy', [], null, 'en') . ' ' . $this->getUserName($damage->getUser()),
                        'de' => $this->translator->trans('ticketAddedBy', [], null, 'de') . ' ' . $this->getUserName($damage->getUser()),
                    ];
                } else {
                    $data['statusText']['janitor'] = [
                        'en' => $this->translator->trans('ticketEditedBy', [], null, 'en') . ' ' . $this->getUserName($damage->getUser()),
                        'de' => $this->translator->trans('ticketEditedBy', [], null, 'de') . ' ' . $this->getUserName($damage->getUser()),
                    ];
                }
                array_push($responsible, 'janitor');
                if ($damage->getApartment()->getProperty()->getUser() != $damage->getApartment()->getProperty()->getJanitor() && $damage->getAllocation() == true) {
                    $this->sendNotificationToUser($damage, $data['statusText']['janitor'], $user, 'janitor', $damage->getApartment()->getProperty()->getJanitor(), 'ticketAddedSubject');
                }
            }
        }
        $data['responsibles'] = $responsible;

        return $data;
    }

    /**
     * @param Damage $damage
     * @param UserIdentity $user
     * @return array
     */
    public function logTicketCreateDetailsByOwnerOrAdminPrivate(Damage $damage, UserIdentity $user): array
    {
        $data = $responsible = [];
        if (!is_null($damage->getDamageOwner())) {
            if ($damage->getApartment()->getProperty()->getUser()) {
                if (is_null($damage->getUpdatedAt())) {
                    $data['statusText']['owner'] = [
                        'en' => $this->translator->trans('ticketAddedBy', [], null, 'en') . ' ' . $this->getUserName($damage->getUser()),
                        'de' => $this->translator->trans('ticketAddedBy', [], null, 'de') . ' ' . $this->getUserName($damage->getUser()),
                    ];
                } else {
                    $data['statusText']['owner'] = [
                        'en' => $this->translator->trans('ticketEditedBy', [], null, 'en') . ' ' . $this->getUserName($user),
                        'de' => $this->translator->trans('ticketEditedBy', [], null, 'de') . ' ' . $this->getUserName($user),
                    ];
                }
                array_push($responsible, 'owner');
                if ($damage->getAllocation() == true) {
                    if (is_null($damage->getApartment()->getProperty()->getAdministrator()))
                        $this->sendNotificationToUser($damage, $data['statusText']['owner'], $user, 'owner', $damage->getApartment()->getProperty()->getUser(), 'ticketAddedSubject');
                }
            }
            if ($damage->getApartment()->getProperty()->getAdministrator()) {
                if (is_null($damage->getUpdatedAt())) {
                    $data['statusText']['property_admin'] = [
                        'en' => $this->translator->trans('ticketAddedBy', [], null, 'en') . ' ' . $this->getUserName($damage->getUser()),
                        'de' => $this->translator->trans('ticketAddedBy', [], null, 'de') . ' ' . $this->getUserName($damage->getUser()),
                    ];
                } else {
                    $data['statusText']['property_admin'] = [
                        'en' => $this->translator->trans('ticketEditedBy', [], null, 'en') . ' ' . $this->getUserName($user),
                        'de' => $this->translator->trans('ticketEditedBy', [], null, 'de') . ' ' . $this->getUserName($user),
                    ];
                }
                array_push($responsible, 'property_admin');
                if ($damage->getApartment()->getProperty()->getUser() != $damage->getApartment()->getProperty()->getAdministrator() && $damage->getAllocation() == true) {
                    $this->sendNotificationToUser($damage, $data['statusText']['property_admin'], $user, 'property_admin', $damage->getApartment()->getProperty()->getAdministrator(), 'ticketAddedSubject');
                }
            }
            if ($damage->getApartment()->getProperty()->getJanitor()) {
                if (is_null($damage->getUpdatedAt())) {
                    $data['statusText']['janitor'] = [
                        'en' => $this->translator->trans('ticketAddedBy', [], null, 'en') . ' ' . $this->getUserName($damage->getUser()),
                        'de' => $this->translator->trans('ticketAddedBy', [], null, 'de') . ' ' . $this->getUserName($damage->getUser()),
                    ];
                } else {
                    $data['statusText']['janitor'] = [
                        'en' => $this->translator->trans('ticketEditedBy', [], null, 'en') . ' ' . $this->getUserName($user),
                        'de' => $this->translator->trans('ticketEditedBy', [], null, 'de') . ' ' . $this->getUserName($user),
                    ];
                }
                array_push($responsible, 'janitor');
                if ($damage->getApartment()->getProperty()->getUser() != $damage->getApartment()->getProperty()->getJanitor() && $damage->getAllocation() == true) {
                    $this->sendNotificationToUser($damage, $data['statusText']['janitor'], $user, 'janitor', $damage->getApartment()->getProperty()->getJanitor(), 'ticketAddedSubject');
                }
            }
        }
        $data['responsibles'] = $responsible;
        return $data;
    }

    /**
     * @param Damage $damage
     * @param array $content
     * @param UserIdentity $currentUser
     * @param string $roleKey
     * @param UserIdentity $toUser
     * @param string $subject
     * @throws
     */
    public function sendNotificationToUser(Damage $damage, array $content, UserIdentity $currentUser,
                                           string $roleKey, UserIdentity $toUser, string $subject): void
    {
        if (!in_array(Constants::ROLE_GUEST, $toUser->getUser()->getRoles())) {
            $companyViewDamage = $this->parameterBag->get('damage_view_url_company');
            $damageView = $this->parameterBag->get('damage_view_url');
            $data = $toUser->getUser()->getProperty() . '#' . $damage->getId() . '#' . $damage->getApartment()->getId();
            $locale = $toUser->getLanguage() ?? $this->parameterBag->get('default_language');
            $companyExist = $this->em->getRepository(DamageRequest::class)->findOneBy(['damage' => $damage, 'company' => $toUser]);
            $emailData['damageUrl'] = $this->parameterBag->get('FE_DOMAIN') . (($companyExist instanceof DamageRequest) ? $companyViewDamage : $damageView);
            $emailData['token'] = $this->containerUtility
                    ->encryptData($data, true, $this->parameterBag->get('token_expiry_hours')) . '&lang=' . $locale;
            $emailData['locale'] = $locale;
            if ($toUser->getLanguage()) {
                $emailData['content'] = $content[$toUser->getLanguage()];
            } else {
                $emailData['content'] = Constants::LANGUAGE_CODES[0];
            }
            $emailData['damage'] = $damage;
            $emailData['apartment'] = $damage->getApartment();
            $mailSubject = $this->translator->trans($subject, [], null, $locale);
            $this->containerUtility->sendEmail($toUser, 'Damage/TicketUpdates', $locale, $subject, $emailData);
            $this->sendPushNotification($toUser, $damage, $mailSubject, $roleKey, $content);
        } else {
            $damageOffer = $this->em->getRepository(DamageOffer::class)->findOneBy(
                ['damage' => $damage, 'company' => $toUser, 'active' => true, 'deleted' => false]);
            $isEdit = false;
            if ($damageOffer instanceof DamageOffer) {
                $isEdit = true;
                $damageRequest = $damageOffer->getDamageRequest();
                $damageRequest->setUpdatedAt(new \DateTime());
                $damageRequest->setStatus($damage->getStatus());
            }
            $this->companyService->sendNonRegisteredCompanyEmailNotification(
                $toUser->getUser()->getProperty(),
                $damage,
                Constants::LANGUAGE_CODES[0],
                $subject, $this->getPortalUrl($damage->getPublicId()), $isEdit, $toUser->getPublicId());
            $damage->setCompanyAssignedBy($currentUser);
            if (!$damage->getCompanyAssignedByRole() instanceof Role) {
                $damage->setCompanyAssignedByRole($damage->getCreatedByRole());
            }
            $this->em->flush();
        }
    }

    /**
     * @param Damage $damage
     * @param array $content
     * @param UserIdentity $currentUser
     * @param string $roleKey
     * @param string $subject
     * @param array $selectedCompanies
     */
    public function sendNotificationToCompanyUser(Damage $damage, array $content, UserIdentity $currentUser,
                                                  string $roleKey, string $subject, array $selectedCompanies = []): void
    {
        $params = ['damage' => $damage, 'deleted' => 0];
        $requestedCompanies = $this->em->getRepository(DamageRequest::class)->findBy($params);
        $companies = [];
        foreach ($requestedCompanies as $request) {
            if ($request->getCompany() instanceof UserIdentity && !in_array($request->getCompany()->getIdentifier(), $companies)
                && in_array($request->getCompany()->getIdentifier(), $selectedCompanies)) {
                $this->sendNotificationToUser($damage, $content, $currentUser, $roleKey, $request->getCompany(), $subject);
                $companies[] = $request->getCompany()->getIdentifier();
            }
        }
    }

    /**
     * @param Damage $damage
     * @param UserIdentity $user
     * @return array
     */
    public function logTicketAcceptDetails(Damage $damage, UserIdentity $user): array
    {
        $data = $responsible = [];
        if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                ->convertCamelCaseString(Constants::OBJECT_OWNER_ROLE)) {
            array_push($responsible, 'object_owner');
            $data['statusText']['object_owner'] = [
                'en' => $this->translator->trans('reviewedAndAccepted', [], null, 'en') . ' ' . $this->getUserName($user),
                'de' => $this->translator->trans('reviewedAndAccepted', [], null, 'de') . ' ' . $this->getUserName($user),
            ];
            $this->sendNotificationToUser($damage, $data['statusText']['object_owner'], $user, 'object_owner', $damage->getUser(), 'reviewedAndAcceptedSubject');
        } else if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                ->convertCamelCaseString(Constants::TENANT_ROLE)) {
            $data['statusText']['tenant'] = [
                'en' => $this->translator->trans('reviewedAndAccepted', [], null, 'en') . ' ' . $this->getUserName($user),
                'de' => $this->translator->trans('reviewedAndAccepted', [], null, 'de') . ' ' . $this->getUserName($user),
            ];
            array_push($responsible, 'tenant');
            $this->sendNotificationToUser($damage, $data['statusText']['tenant'], $user, 'tenant', $damage->getUser(), 'reviewedAndAcceptedSubject');
        }
        if (!is_null($damage->getDamageOwner())) {
            if ($damage->getApartment()->getProperty()->getUser()) {
                $data['statusText']['owner'] = [
                    'en' => $this->translator->trans('reviewedAndAccepted', [], null, 'en') . ' ' . $this->getUserName($user),
                    'de' => $this->translator->trans('reviewedAndAccepted', [], null, 'de') . ' ' . $this->getUserName($user),
                ];
                array_push($responsible, 'owner');
            }
            if ($damage->getApartment()->getProperty()->getAdministrator()) {
                $data['statusText']['property_admin'] = [
                    'en' => $this->translator->trans('reviewedAndAccepted', [], null, 'en') . ' ' . $this->getUserName($user),
                    'de' => $this->translator->trans('reviewedAndAccepted', [], null, 'de') . ' ' . $this->getUserName($user),
                ];
                array_push($responsible, 'property_admin');
            }
            if ($damage->getApartment()->getProperty()->getJanitor()) {
                $data['statusText']['janitor'] = [
                    'en' => $this->translator->trans('reviewedAndAccepted', [], null, 'en') . ' ' . $this->getUserName($user),
                    'de' => $this->translator->trans('reviewedAndAccepted', [], null, 'de') . ' ' . $this->getUserName($user),
                ];
                array_push($responsible, 'janitor');
            }
        }
        $data['responsibles'] = $responsible;

        return $data;
    }

    /**
     * @param Damage $damage
     * @param UserIdentity $user
     * @param array|null $requestedCompanies
     * @return array
     */
    public function logTicketSendToCompanyWithoutOffer(Damage $damage, UserIdentity $user, ?array $requestedCompanies = []): array
    {
        $data = $responsible = [];
        if (!is_null($damage->getDamageOwner())) {
            $companies = $this->getOfferRequestedCompanyDetails($requestedCompanies);
            if ($damage->getApartment()->getProperty()->getUser()) {
                $data['statusText']['owner'] = [
                    'en' => $this->translator->trans('offerRequested', [], null, 'en') . (!empty(trim($companies)) ? ' : ' . $companies : ''),
                    'de' => $this->translator->trans('offerRequested', [], null, 'de') . (!empty(trim($companies)) ? ' : ' . $companies : ''),
                ];
                array_push($responsible, 'owner');
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('offerRequestReceived', [], null, 'en') . ' ' . $this->getUserName($user),
                    'de' => $this->translator->trans('offerRequestReceived', [], null, 'de') . ' ' . $this->getUserName($user),
                ];
            }
            foreach ($requestedCompanies as $companyId) {
                $data['statusText']['company' . $companyId] = [
                    'en' => $this->translator->trans('offerRequestReceived', [], null, 'en') . ' ' . $this->getUserName($user),
                    'de' => $this->translator->trans('offerRequestReceived', [], null, 'de') . ' ' . $this->getUserName($user),
                ];
            }
            array_push($responsible, 'company');
            if ($damage->getApartment()->getProperty()->getAdministrator()) {
                $data['statusText']['property_admin'] = [
                    'en' => $this->translator->trans('offerRequested', [], null, 'en') . (!empty(trim($companies)) ? ' : ' . $companies : ''),
                    'de' => $this->translator->trans('offerRequested', [], null, 'de') . (!empty(trim($companies)) ? ' : ' . $companies : ''),
                ];
                array_push($responsible, 'property_admin');
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('offerRequestReceived', [], null, 'en') . ' ' . $this->getUserName($user),
                    'de' => $this->translator->trans('offerRequestReceived', [], null, 'de') . ' ' . $this->getUserName($user),
                ];
            }
            if ($damage->getApartment()->getProperty()->getJanitor()) {
                $data['statusText']['janitor'] = [
                    'en' => $this->translator->trans('offerRequested', [], null, 'en') . (!empty(trim($companies)) ? ' : ' . $companies : ''),
                    'de' => $this->translator->trans('offerRequested', [], null, 'de') . (!empty(trim($companies)) ? ' : ' . $companies : ''),
                ];
                array_push($responsible, 'janitor');
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('offerRequestReceived', [], null, 'en') . ' ' . $this->getUserName($user),
                    'de' => $this->translator->trans('offerRequestReceived', [], null, 'de') . ' ' . $this->getUserName($user),
                ];
            }
            $this->sendNotificationToCompanyUser($damage, $data['statusText']['company'], $user, 'company', 'offerRequestedSubject', $requestedCompanies);
        }
        $data['responsibles'] = $responsible;

        return $data;

    }

    /**
     * @param Damage $damage
     * @param UserIdentity $user
     * @param array|null $requestedCompanies
     * @return array
     */
    public function logTicketSendToCompanyByPrivateWithoutOffer(Damage $damage, UserIdentity $user, ?array $requestedCompanies = []): array
    {
        $data = $responsible = [];
        if (!is_null($damage->getDamageOwner())) {
            $companies = $this->getOfferRequestedCompanyDetails($requestedCompanies);
            if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::OBJECT_OWNER_ROLE)) {
                array_push($responsible, 'object_owner');
                array_push($responsible, 'company');
                $data['statusText']['object_owner'] = [
                    'en' => $this->translator->trans('offerRequested', [], null, 'en') . (!empty(trim($companies)) ? ' : ' . $companies : ''),
                    'de' => $this->translator->trans('offerRequested', [], null, 'de') . (!empty(trim($companies)) ? ' : ' . $companies : ''),
                ];
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('offerRequestReceived', [], null, 'en') . ' ' . $this->getUserName($damage->getUser()),
                    'de' => $this->translator->trans('offerRequestReceived', [], null, 'de') . ' ' . $this->getUserName($damage->getUser()),
                ];
                foreach ($requestedCompanies as $companyId) {
                    $data['statusText']['company' . $companyId] = [
                        'en' => $this->translator->trans('offerRequestReceived', [], null, 'en') . ' ' . $this->getUserName($damage->getUser()),
                        'de' => $this->translator->trans('offerRequestReceived', [], null, 'de') . ' ' . $this->getUserName($damage->getUser()),
                    ];
                }
                //$this->sendNotificationToUser($damage, $data['statusText']['object_owner'][$damage->getUser()->getLanguage()], $user, 'object_owner', $damage->getUser(), 'offerRequestedSubject');
            } else if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::TENANT_ROLE)) {
                $data['statusText']['tenant'] = [
                    'en' => $this->translator->trans('offerRequested', [], null, 'en') . (!empty(trim($companies)) ? ' : ' . $companies : ''),
                    'de' => $this->translator->trans('offerRequested', [], null, 'de') . (!empty(trim($companies)) ? ' : ' . $companies : ''),
                ];
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('offerRequestReceived', [], null, 'en') . ' ' . $this->getUserName($damage->getUser()),
                    'de' => $this->translator->trans('offerRequestReceived', [], null, 'de') . ' ' . $this->getUserName($damage->getUser()),
                ];
                foreach ($requestedCompanies as $companyId) {
                    $data['statusText']['company' . $companyId] = [
                        'en' => $this->translator->trans('offerRequestReceived', [], null, 'en') . ' ' . $this->getUserName($damage->getUser()),
                        'de' => $this->translator->trans('offerRequestReceived', [], null, 'de') . ' ' . $this->getUserName($damage->getUser()),
                    ];
                }
                array_push($responsible, 'tenant');
                array_push($responsible, 'company');
                //$this->sendNotificationToUser($damage, $data['statusText']['tenant'][$damage->getUser()->getLanguage()], $user, 'tenant', $damage->getUser(), 'offerRequestedSubject');
            }
            $this->sendNotificationToCompanyUser($damage, $data['statusText']['company'], $user, 'company', 'offerRequestedSubject', $requestedCompanies);
        }
        $data['responsibles'] = $responsible;

        return $data;
    }

    /**
     * @param Damage $damage
     * @param UserIdentity $user
     * @return array
     */
    public function logCompanyOfferDetails(Damage $damage, UserIdentity $user): array
    {
        $data = $responsible = [];
        if (!is_null($damage->getDamageOwner())) {
            $damageOffer = $this->em->getRepository(DamageOffer::class)->getOfferPrice($user);
            $amount = $damageOffer['amount'] ? number_format((float)($damageOffer['amount']), 2, '.', '') : null;
            if ($damage->getApartment()->getProperty()->getUser()) {
                $data['statusText']['owner'] = [
                    'en' => $this->translator->trans('offerReceivedFrom', [], null, 'en') . ' ' . $user->getCompanyName() . ' ' .
                        $this->translator->trans('totalChf') . ' ' . $amount,
                    'de' => $this->translator->trans('offerReceivedFrom', [], null, 'de') . ' ' . $user->getCompanyName() . ' ' .
                        $this->translator->trans('totalChf') . ' ' . $amount,
                ];
                array_push($responsible, 'owner');
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('offerSent', [], null, 'en') . ' : ' . $this->translator->trans('totalChf')
                        . ' ' . $amount,
                    'de' => $this->translator->trans('offerSent', [], null, 'de') . ' : ' . $this->translator->trans('totalChf')
                        . ' ' . $amount,
                ];
                $data['preferredCompany'] = $damageOffer['company'];
                array_push($responsible, 'company');
                if (is_null($damage->getApartment()->getProperty()->getAdministrator()))
                    $this->sendNotificationToUser($damage, $data['statusText']['owner'], $user, 'owner', $damage->getApartment()->getProperty()->getUser(), 'offerReceivedFromSubject');
            }
            if ($damage->getApartment()->getProperty()->getAdministrator()) {
                $data['statusText']['property_admin'] = [
                    'en' => $this->translator->trans('offerReceivedFrom', [], null, 'en') . ' ' . $user->getCompanyName() . ' ' .
                        $this->translator->trans('totalChf') . ' ' . $amount,
                    'de' => $this->translator->trans('offerReceivedFrom', [], null, 'de') . ' ' . $user->getCompanyName() . ' ' .
                        $this->translator->trans('totalChf') . ' ' . $amount,
                ];
                array_push($responsible, 'property_admin');
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('offerSent', [], null, 'en') . ' : ' . $this->translator->trans('totalChf')
                        . ' ' . $amount,
                    'de' => $this->translator->trans('offerSent', [], null, 'de') . ' : ' . $this->translator->trans('totalChf')
                        . ' ' . $amount,
                ];
                $data['preferredCompany'] = $damageOffer['company'];
                array_push($responsible, 'company');
                if ($damage->getApartment()->getProperty()->getUser() != $damage->getApartment()->getProperty()->getAdministrator()) {
                    $this->sendNotificationToUser($damage, $data['statusText']['property_admin'], $user, 'property_admin', $damage->getApartment()->getProperty()->getAdministrator(), 'offerReceivedFromSubject');
                }
            }
            if ($damage->getApartment()->getProperty()->getJanitor()) {
                $data['statusText']['janitor'] = [
                    'en' => $this->translator->trans('offerReceivedFrom', [], null, 'en') . ' ' . $user->getCompanyName() . ' ' .
                        $this->translator->trans('totalChf') . ' ' . $amount,
                    'de' => $this->translator->trans('offerReceivedFrom', [], null, 'de') . ' ' . $user->getCompanyName() . ' ' .
                        $this->translator->trans('totalChf') . ' ' . $amount,
                ];
                array_push($responsible, 'janitor');
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('offerSent', [], null, 'en') . ' : ' . $this->translator->trans('totalChf')
                        . ' ' . $amount,
                    'de' => $this->translator->trans('offerSent', [], null, 'de') . ' : ' . $this->translator->trans('totalChf')
                        . ' ' . $amount,
                ];
                $data['preferredCompany'] = $damageOffer['company'];
                array_push($responsible, 'company');
                if ($damage->getApartment()->getProperty()->getUser() != $damage->getApartment()->getProperty()->getJanitor()) {
                    $this->sendNotificationToUser($damage, $data['statusText']['janitor'], $user, 'janitor', $damage->getApartment()->getProperty()->getJanitor(), 'offerReceivedFromSubject');
                }
            }
        }
        $data['responsibles'] = $responsible;

        return $data;

    }

    /**
     * @param Damage $damage
     * @param UserIdentity $user
     * @return array
     */
    public function logCompanyOfferDetailsToPrivate(Damage $damage, UserIdentity $user): array
    {
        $data = $responsible = [];
        if (!is_null($damage->getDamageOwner())) {
            $damageOffer = $this->em->getRepository(DamageOffer::class)->getOfferPrice($user);
            $amount = $damageOffer['amount'] ? number_format((float)($damageOffer['amount']), 2, '.', '') : null;
            if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::OBJECT_OWNER_ROLE)) {
                array_push($responsible, 'object_owner');
                array_push($responsible, 'company');
                $data['statusText']['object_owner'] = [
                    'en' => $this->translator->trans('offerReceivedFrom', [], null, 'en') . ' ' . $user->getCompanyName() . ' ' .
                        $this->translator->trans('totalChf') . ' ' . $amount,
                    'de' => $this->translator->trans('offerReceivedFrom', [], null, 'de') . ' ' . $user->getCompanyName() . ' ' .
                        $this->translator->trans('totalChf') . ' ' . $amount,
                ];
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('offerSent', [], null, 'en') . ' : ' . $this->translator->trans('totalChf')
                        . ' ' . $amount,
                    'de' => $this->translator->trans('offerSent', [], null, 'de') . ' : ' . $this->translator->trans('totalChf')
                        . ' ' . $amount,
                ];
                $data['preferredCompany'] = $damageOffer['company'];
            } else if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::TENANT_ROLE)) {
                $data['statusText']['tenant'] = [
                    'en' => $this->translator->trans('offerReceivedFrom', [], null, 'en') . ' ' . $user->getCompanyName() . ' ' .
                        $this->translator->trans('totalChf') . ' ' . $amount,
                    'de' => $this->translator->trans('offerReceivedFrom', [], null, 'de') . ' ' . $user->getCompanyName() . ' ' .
                        $this->translator->trans('totalChf') . ' ' . $amount,
                ];
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('offerSent', [], null, 'en') . ' : ' . $this->translator->trans('totalChf')
                        . ' ' . $amount,
                    'de' => $this->translator->trans('offerSent', [], null, 'de') . ' : ' . $this->translator->trans('totalChf')
                        . ' ' . $amount,
                ];
                $data['preferredCompany'] = $damageOffer['company'];
                array_push($responsible, 'tenant');
                array_push($responsible, 'company');
            }
        }
        $data['responsibles'] = $responsible;

        return $data;
    }

    /**
     * @param Damage $damage
     * @param UserIdentity $user
     * @return array
     */
    public function logOfferRequestAcceptedByCompany(Damage $damage, UserIdentity $user): array
    {
        $data = $responsible = [];
        if (!is_null($damage->getDamageOwner())) {
            $companyName = $user->getCompanyName();
            if ($damage->getApartment()->getProperty()->getUser()) {
                $data['statusText']['owner'] = [
                    'en' => $this->translator->trans('offerRequestAccepted', [], null, 'en') . ' ' . $companyName,
                    'de' => $this->translator->trans('offerRequestAccepted', [], null, 'de') . ' ' . $companyName,
                ];
                array_push($responsible, 'owner');
                $this->sendNotificationToUser($damage, $data['statusText']['owner'], $user, 'owner', $damage->getApartment()->getProperty()->getUser(), 'offerRequestAcceptedSubject');
            }
            if ($damage->getApartment()->getProperty()->getAdministrator()) {
                $data['statusText']['property_admin'] = [
                    'en' => $this->translator->trans('offerRequestAccepted', [], null, 'en') . ' ' . $companyName,
                    'de' => $this->translator->trans('offerRequestAccepted', [], null, 'de') . ' ' . $companyName,
                ];
                array_push($responsible, 'property_admin');
                $this->sendNotificationToUser($damage, $data['statusText']['property_admin'], $user, 'property_admin', $damage->getApartment()->getProperty()->getAdministrator(), 'offerRequestAcceptedSubject');
            }
            if ($damage->getApartment()->getProperty()->getJanitor()) {
                $data['statusText']['janitor'] = [
                    'en' => $this->translator->trans('offerRequestAccepted', [], null, 'en') . ' ' . $companyName,
                    'de' => $this->translator->trans('offerRequestAccepted', [], null, 'de') . ' ' . $companyName,
                ];
                array_push($responsible, 'janitor');
                $this->sendNotificationToUser($damage, $data['statusText']['janitor'], $user, 'janitor', $damage->getApartment()->getProperty()->getJanitor(), 'offerRequestAcceptedSubject');
            }
            if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::OBJECT_OWNER_ROLE)) {
                $data['statusText']['object_owner'] = [
                    'en' => $this->translator->trans('offerRequestAccepted', [], null, 'en') . ' : ' . $companyName,
                    'de' => $this->translator->trans('offerRequestAccepted', [], null, 'de') . ' : ' . $companyName,
                ];
                array_push($responsible, 'object_owner');
                $this->sendNotificationToUser($damage, $data['statusText']['object_owner'], $user, 'object_owner', $damage->getUser(), 'offerRequestAcceptedSubject');
            } else if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::TENANT_ROLE)) {
                $data['statusText']['tenant'] = [
                    'en' => $this->translator->trans('offerRequestAccepted', [], null, 'en') . ' : ' . $companyName,
                    'de' => $this->translator->trans('offerRequestAccepted', [], null, 'de') . ' : ' . $companyName,
                ];
                array_push($responsible, 'tenant');
                $this->sendNotificationToUser($damage, $data['statusText']['tenant'], $user, 'tenant', $damage->getUser(), 'offerRequestAcceptedSubject');
            }
        }
        $data['responsibles'] = $responsible;

        return $data;

    }

    /**
     * @param Damage $damage
     * @param UserIdentity $user
     * @param DamageOffer $damageOffer
     * @return array
     */
    public function logOfferGivenFromCompany(Damage $damage, UserIdentity $user, DamageOffer $damageOffer): array
    {
        $data = $responsible = [];
        if (!is_null($damage->getDamageOwner())) {
            $companyObj = $damageOffer->getCompany();
            $companyName = $damageOffer->getCompany()->getCompanyName();
            if ($damage->getApartment()->getProperty()->getUser()) {
                $data['statusText']['owner'] = [
                    'en' => $this->translator->trans('orderPlaceWith', [], null, 'en') . ' ' . $companyName,
                    'de' => $this->translator->trans('orderPlaceWith', [], null, 'de') . ' ' . $companyName,
                ];
                array_push($responsible, 'owner');
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('offerAccepted', [], null, 'en'),
                    'de' => $this->translator->trans('offerAccepted', [], null, 'de'),
                ];
                array_push($responsible, 'company');
                $data['preferredCompany'] = $companyObj->getIdentifier();
                $this->sendNotificationToUser($damage, $data['statusText']['company'], $user, 'company', $companyObj, 'offerAcceptedSubject');
            }
            if ($damage->getApartment()->getProperty()->getAdministrator()) {
                $data['statusText']['property_admin'] = [
                    'en' => $this->translator->trans('orderPlaceWith', [], null, 'en') . ' ' . $companyName,
                    'de' => $this->translator->trans('orderPlaceWith', [], null, 'de') . ' ' . $companyName,
                ];
                array_push($responsible, 'property_admin');
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('offerAccepted', [], null, 'en'),
                    'de' => $this->translator->trans('offerAccepted', [], null, 'de'),
                ];
                $data['preferredCompany'] = $companyObj->getIdentifier();
                array_push($responsible, 'company');
                //$this->sendNotificationToUser($damage, $data['statusText']['company'][$companyObj->getLanguage()], $user, 'company', $companyObj, 'offerAcceptedSubject');
            }
            if ($damage->getApartment()->getProperty()->getJanitor()) {
                $data['statusText']['janitor'] = [
                    'en' => $this->translator->trans('orderPlaceWith', [], null, 'en') . ' ' . $companyName,
                    'de' => $this->translator->trans('orderPlaceWith', [], null, 'de') . ' ' . $companyName,
                ];
                array_push($responsible, 'janitor');
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('offerAccepted', [], null, 'en'),
                    'de' => $this->translator->trans('offerAccepted', [], null, 'de'),
                ];
                $data['preferredCompany'] = $companyObj->getIdentifier();
                array_push($responsible, 'company');
                //$this->sendNotificationToUser($damage, $data['statusText']['company'][$companyObj->getLanguage()], $user, 'company', $companyObj, 'offerAcceptedSubject');
            }
            if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::OBJECT_OWNER_ROLE)) {
                $data['statusText']['object_owner'] = [
                    'en' => $this->translator->trans('companyAssigned', [], null, 'en') . ' : ' . $companyName,
                    'de' => $this->translator->trans('companyAssigned', [], null, 'de') . ' : ' . $companyName,
                ];
                array_push($responsible, 'object_owner');
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('offerAccepted', [], null, 'en'),
                    'de' => $this->translator->trans('offerAccepted', [], null, 'de'),
                ];
                $data['preferredCompany'] = $companyObj->getIdentifier();
                array_push($responsible, 'company');
                $this->sendNotificationToUser($damage, $data['statusText']['object_owner'], $user, 'object_owner', $damage->getUser(), 'companyAssignedSubject');
                $this->sendNotificationToUser($damage, $data['statusText']['company'], $user, 'company', $companyObj, 'offerAcceptedSubject');
            } else if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::TENANT_ROLE)) {
                $data['statusText']['tenant'] = [
                    'en' => $this->translator->trans('companyAssigned', [], null, 'en') . ' : ' . $companyName,
                    'de' => $this->translator->trans('companyAssigned', [], null, 'de') . ' : ' . $companyName,
                ];
                array_push($responsible, 'tenant');
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('offerAccepted', [], null, 'en'),
                    'de' => $this->translator->trans('offerAccepted', [], null, 'de'),
                ];
                $data['preferredCompany'] = $companyObj->getIdentifier();
                array_push($responsible, 'company');
                $this->sendNotificationToUser($damage, $data['statusText']['tenant'], $user, 'tenant', $damage->getUser(), 'companyAssignedSubject');
                $this->sendNotificationToUser($damage, $data['statusText']['company'], $user, 'company', $companyObj, 'offerAcceptedSubject');
            }
        }
        $data['responsibles'] = $responsible;

        return $data;
    }

    /**
     * @param Damage $damage
     * @param UserIdentity $user
     * @param DamageOffer $damageOffer
     * @return array
     */
    public function logOfferGivenFromCompanyToPrivate(Damage $damage, UserIdentity $user, DamageOffer $damageOffer): array
    {
        $data = $responsible = [];
        if (!is_null($damage->getDamageOwner())) {
            $companyObj = $damageOffer->getCompany();
            $companyName = $damageOffer->getCompany()->getCompanyName();
            if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::OBJECT_OWNER_ROLE)) {
                $data['statusText']['object_owner'] = [
                    'en' => $this->translator->trans('companyAssigned', [], null, 'en') . ' : ' . $companyName,
                    'de' => $this->translator->trans('companyAssigned', [], null, 'de') . ' : ' . $companyName,
                ];
                array_push($responsible, 'object_owner');
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('offerAccepted', [], null, 'en'),
                    'de' => $this->translator->trans('offerAccepted', [], null, 'de'),
                ];
                $data['preferredCompany'] = $companyObj->getIdentifier();
                array_push($responsible, 'company');
                $this->sendNotificationToUser($damage, $data['statusText']['object_owner'], $user, 'object_owner', $damage->getUser(), 'companyAssignedSubject');
                $this->sendNotificationToUser($damage, $data['statusText']['company'], $user, 'company', $companyObj, 'offerAcceptedSubject');
            } else if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::TENANT_ROLE)) {
                $data['statusText']['tenant'] = [
                    'en' => $this->translator->trans('companyAssigned', [], null, 'en') . ' : ' . $companyName,
                    'de' => $this->translator->trans('companyAssigned', [], null, 'de') . ' : ' . $companyName,
                ];
                array_push($responsible, 'tenant');
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('offerAccepted', [], null, 'en'),
                    'de' => $this->translator->trans('offerAccepted', [], null, 'de'),
                ];
                $data['preferredCompany'] = $companyObj->getIdentifier();
                array_push($responsible, 'company');
                $this->sendNotificationToUser($damage, $data['statusText']['tenant'], $user, 'tenant', $damage->getUser(), 'companyAssignedSubject');
                $this->sendNotificationToUser($damage, $data['statusText']['company'], $user, 'company', $companyObj, 'offerAcceptedSubject');
            }
        }
        $data['responsibles'] = $responsible;

        return $data;
    }

    /**
     * @param Damage $damage
     * @param UserIdentity $user
     * @return array
     */
    public function logScheduledDateFromCompany(Damage $damage, UserIdentity $user): array
    {
        $data = $responsible = [];
        if (!is_null($damage->getDamageOwner())) {
            $appointment = $this->em->getRepository(DamageAppointment::class)->findLatestAppointmentDate($damage);
            if (is_null($appointment)) return $data;
            $reschedule = count($this->em->getRepository(DamageAppointment::class)->findBy(['damage' => $damage, 'deleted' => 0])) > 1;
            $logText = ($reschedule == true) ? 'appointmentRescheduled' : 'appointmentFixed';
            $mailSubjectText = ($reschedule == true) ? 'appointmentRescheduledSubject' : 'appointmentFixedSubject';
            $appointmentDate = $appointment['scheduledTime']->format('d.m.Y H:i');
            $company = $this->em->getRepository(DamageOffer::class)->findCompanyWithOffer($damage);
            if ($damage->getApartment()->getProperty()->getUser()) {
                $data['statusText']['owner'] = [
                    'en' => $this->translator->trans($logText, [], null, 'en') . ' : ' . $appointmentDate,
                    'de' => $this->translator->trans($logText, [], null, 'de') . ' : ' . $appointmentDate,
                ];
                array_push($responsible, 'owner');
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans($logText, [], null, 'en') . ' : ' . $appointmentDate,
                    'de' => $this->translator->trans($logText, [], null, 'de') . ' : ' . $appointmentDate,
                ];
                $data['preferredCompany'] = $company['company'];
                array_push($responsible, 'company');
                if (is_null($damage->getApartment()->getProperty()->getAdministrator()))
                    $this->sendNotificationToUser($damage, $data['statusText']['owner'], $user, 'owner', $damage->getApartment()->getProperty()->getUser(), $mailSubjectText);
            }
            if ($damage->getApartment()->getProperty()->getAdministrator()) {
                $data['statusText']['property_admin'] = [
                    'en' => $this->translator->trans($logText, [], null, 'en') . ' : ' . $appointmentDate,
                    'de' => $this->translator->trans($logText, [], null, 'de') . ' : ' . $appointmentDate,
                ];
                array_push($responsible, 'property_admin');
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans($logText, [], null, 'en') . ' : ' . $appointmentDate,
                    'de' => $this->translator->trans($logText, [], null, 'de') . ' : ' . $appointmentDate,
                ];
                $data['preferredCompany'] = $company['company'];
                array_push($responsible, 'company');
                if ($damage->getApartment()->getProperty()->getUser() !== $damage->getApartment()->getProperty()->getAdministrator()) {
                    $this->sendNotificationToUser($damage, $data['statusText']['property_admin'], $user, 'property_admin', $damage->getApartment()->getProperty()->getAdministrator(), $mailSubjectText);
                }
            }
            if ($damage->getApartment()->getProperty()->getJanitor()) {
                $data['statusText']['janitor'] = [
                    'en' => $this->translator->trans($logText, [], null, 'en') . ' : ' . $appointmentDate,
                    'de' => $this->translator->trans($logText, [], null, 'de') . ' : ' . $appointmentDate,
                ];
                array_push($responsible, 'janitor');
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans($logText, [], null, 'en') . ' : ' . $appointmentDate,
                    'de' => $this->translator->trans($logText, [], null, 'de') . ' : ' . $appointmentDate,
                ];
                $data['preferredCompany'] = $company['company'];
                array_push($responsible, 'company');
                if ($damage->getApartment()->getProperty()->getUser() !== $damage->getApartment()->getProperty()->getJanitor()) {
                    $this->sendNotificationToUser($damage, $data['statusText']['janitor'], $user, 'janitor', $damage->getApartment()->getProperty()->getJanitor(), $mailSubjectText);
                }
            }
            if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::OBJECT_OWNER_ROLE)) {
                $data['statusText']['object_owner'] = [
                    'en' => $this->translator->trans($logText, [], null, 'en') . ' : ' . $appointmentDate,
                    'de' => $this->translator->trans($logText, [], null, 'de') . ' : ' . $appointmentDate,
                ];
                array_push($responsible, 'object_owner');
                $this->sendNotificationToUser($damage, $data['statusText']['object_owner'], $user, 'object_owner', $damage->getUser(), $mailSubjectText);
            } else if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::TENANT_ROLE)) {
                $data['statusText']['tenant'] = [
                    'en' => $this->translator->trans($logText, [], null, 'en') . ' : ' . $appointmentDate,
                    'de' => $this->translator->trans($logText, [], null, 'de') . ' : ' . $appointmentDate,
                ];
                array_push($responsible, 'tenant');
                $this->sendNotificationToUser($damage, $data['statusText']['tenant'], $user, 'tenant', $damage->getUser(), $mailSubjectText);
            }
        }
        $data['responsibles'] = $responsible;

        return $data;
    }

    /**
     * @param Damage $damage
     * @param UserIdentity $user
     * @return array
     */
    public function logScheduledDateFromCompanyToPrivate(Damage $damage, UserIdentity $user): array
    {
        $data = $responsible = [];
        if (!is_null($damage->getDamageOwner())) {
            $appointment = $this->em->getRepository(DamageAppointment::class)->findLatestAppointmentDate($damage);
            if (is_null($appointment)) return $data;
            $reschedule = count($this->em->getRepository(DamageAppointment::class)->findBy(['damage' => $damage, 'deleted' => 0])) > 1;
            $logText = ($reschedule == true) ? 'appointmentRescheduled' : 'appointmentFixed';
            $mailSubjectText = ($reschedule == true) ? 'appointmentRescheduledSubject' : 'appointmentFixedSubject';
            $appointmentDate = $appointment['scheduledTime']->format('d.m.Y H:i');
            $company = $this->em->getRepository(DamageOffer::class)->findCompanyWithOffer($damage);
            if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::OBJECT_OWNER_ROLE)) {
                $data['statusText']['object_owner'] = [
                    'en' => $this->translator->trans($logText, [], null, 'en') . ' : ' . $appointmentDate,
                    'de' => $this->translator->trans($logText, [], null, 'de') . ' : ' . $appointmentDate,
                ];
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans($logText, [], null, 'en') . ' : ' . $appointmentDate,
                    'de' => $this->translator->trans($logText, [], null, 'de') . ' : ' . $appointmentDate,
                ];
                $data['preferredCompany'] = $company['company'];
                array_push($responsible, 'company');
                array_push($responsible, 'object_owner');
                $this->sendNotificationToUser($damage, $data['statusText']['object_owner'], $user, 'object_owner', $damage->getUser(), $mailSubjectText);
            } else if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::TENANT_ROLE)) {
                $data['statusText']['tenant'] = [
                    'en' => $this->translator->trans($logText, [], null, 'en') . ' : ' . $appointmentDate,
                    'de' => $this->translator->trans($logText, [], null, 'de') . ' : ' . $appointmentDate,
                ];
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans($logText, [], null, 'en') . ' : ' . $appointmentDate,
                    'de' => $this->translator->trans($logText, [], null, 'de') . ' : ' . $appointmentDate,
                ];
                $data['preferredCompany'] = $company['company'];
                array_push($responsible, 'company');
                array_push($responsible, 'tenant');
                $this->sendNotificationToUser($damage, $data['statusText']['tenant'], $user, 'tenant', $damage->getUser(), $mailSubjectText);
            }
        }
        $data['responsibles'] = $responsible;

        return $data;
    }

    /**
     * @param Damage $damage
     * @param UserIdentity $user
     * @return array
     */
    public function logRepairConfirmedDetails(Damage $damage, UserIdentity $user): array
    {
        $data = $responsible = [];
        if (!is_null($damage->getDamageOwner())) {
            $closedBy = $this->getUserName($user);
            $company = $this->em->getRepository(DamageOffer::class)->findCompanyWithOffer($damage);
            $companyObj = !is_null($company) ? $this->em->getRepository(UserIdentity::class)->findOneBy(['identifier' => $company['company']]) : null;
            if ($damage->getApartment()->getProperty()->getUser()) {
                $data['statusText']['owner'] = [
                    'en' => $this->translator->trans('ticketClosedBy', [], null, 'en') . ' : ' . $closedBy,
                    'de' => $this->translator->trans('ticketClosedBy', [], null, 'de') . ' : ' . $closedBy,
                ];
                array_push($responsible, 'owner');
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('ticketClosedBy', [], null, 'en') . ' : ' . $closedBy,
                    'de' => $this->translator->trans('ticketClosedBy', [], null, 'de') . ' : ' . $closedBy,
                ];
                array_push($responsible, 'company');
                $data['preferredCompany'] = !is_null($companyObj) ? $company['company'] : '';
                if (is_null($damage->getApartment()->getProperty()->getAdministrator())) {
                    $this->sendNotificationToUser($damage, $data['statusText']['owner'], $user, 'owner', $damage->getApartment()->getProperty()->getUser(), 'ticketClosedSubject');
                }
                $companyObj instanceof UserIdentity ? $this->sendNotificationToUser($damage, $data['statusText']['company'], $user, 'company', $companyObj, 'ticketClosedSubject') : '';
            }
            if ($damage->getApartment()->getProperty()->getAdministrator()) {
                $data['statusText']['property_admin'] = [
                    'en' => $this->translator->trans('ticketClosedBy', [], null, 'en') . ' : ' . $closedBy,
                    'de' => $this->translator->trans('ticketClosedBy', [], null, 'de') . ' : ' . $closedBy,
                ];
                array_push($responsible, 'property_admin');
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('ticketClosedBy', [], null, 'en') . ' : ' . $closedBy,
                    'de' => $this->translator->trans('ticketClosedBy', [], null, 'de') . ' : ' . $closedBy,
                ];
                array_push($responsible, 'company');
                $data['preferredCompany'] = !is_null($companyObj) ? $company['company'] : '';
                if ($damage->getApartment()->getProperty()->getUser() != $damage->getApartment()->getProperty()->getAdministrator()) {
                    $this->sendNotificationToUser($damage, $data['statusText']['property_admin'], $user, 'property_admin', $damage->getApartment()->getProperty()->getAdministrator(), 'ticketClosedSubject');
                }
                //$this->sendNotificationToUser($damage, $data['statusText']['company'][$companyObj->getLanguage()], $user, 'company', $companyObj, 'ticketClosedSubject');
            }
            if ($damage->getApartment()->getProperty()->getJanitor()) {
                $data['statusText']['janitor'] = [
                    'en' => $this->translator->trans('ticketClosedBy', [], null, 'en') . ' : ' . $closedBy,
                    'de' => $this->translator->trans('ticketClosedBy', [], null, 'de') . ' : ' . $closedBy,
                ];
                array_push($responsible, 'janitor');
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('ticketClosedBy', [], null, 'en') . ' : ' . $closedBy,
                    'de' => $this->translator->trans('ticketClosedBy', [], null, 'de') . ' : ' . $closedBy,
                ];
                array_push($responsible, 'company');
                $data['preferredCompany'] = !is_null($companyObj) ? $company['company'] : '';
                if ($damage->getApartment()->getProperty()->getUser() != $damage->getApartment()->getProperty()->getJanitor()) {
                    $this->sendNotificationToUser($damage, $data['statusText']['janitor'], $user, 'janitor', $damage->getApartment()->getProperty()->getJanitor(), 'ticketClosedSubject');
                }
                //$this->sendNotificationToUser($damage, $data['statusText']['company'][$companyObj->getLanguage()], $user, 'company', $companyObj, 'ticketClosedSubject');
            }
            if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::OBJECT_OWNER_ROLE)) {
                $data['statusText']['object_owner'] = [
                    'en' => $this->translator->trans('ticketClosedBy', [], null, 'en') . ' ' . $closedBy,
                    'de' => $this->translator->trans('ticketClosedBy', [], null, 'de') . ' ' . $closedBy,
                ];
                array_push($responsible, 'object_owner');
            } else if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::TENANT_ROLE)) {
                $data['statusText']['tenant'] = [
                    'en' => $this->translator->trans('ticketClosedBy', [], null, 'en') . ' ' . $closedBy,
                    'de' => $this->translator->trans('ticketClosedBy', [], null, 'de') . ' ' . $closedBy,
                ];
                array_push($responsible, 'tenant');
            }
        }
        $data['responsibles'] = $responsible;

        return $data;
    }

    /**
     * @param Damage $damage
     * @param UserIdentity $user
     * @return array
     */
    public function logRepairConfirmedDetailsByPrivate(Damage $damage, UserIdentity $user): array
    {
        $data = $responsible = [];
        if (!is_null($damage->getDamageOwner())) {
            $closedBy = $this->getUserName($user);
            $company = $this->em->getRepository(DamageOffer::class)->findCompanyWithOffer($damage);
            $companyObj = !is_null($company) ? $this->em->getRepository(UserIdentity::class)->findOneBy(['identifier' => $company['company']]) : null;
            if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::OBJECT_OWNER_ROLE)) {
                $data['statusText']['object_owner'] = [
                    'en' => $this->translator->trans('ticketClosedBy', [], null, 'en') . ' ' . $closedBy,
                    'de' => $this->translator->trans('ticketClosedBy', [], null, 'de') . ' ' . $closedBy,
                ];
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('ticketClosedBy', [], null, 'en') . ' : ' . $closedBy,
                    'de' => $this->translator->trans('ticketClosedBy', [], null, 'de') . ' : ' . $closedBy,
                ];
                $data['preferredCompany'] = !is_null($companyObj) ? $company['company'] : '';
                array_push($responsible, 'company');
                array_push($responsible, 'object_owner');
                $data['statusText']['property_admin'] = [
                    'en' => $this->translator->trans('ticketClosedBy', [], null, 'en') . ' : ' . $closedBy,
                    'de' => $this->translator->trans('ticketClosedBy', [], null, 'de') . ' : ' . $closedBy,
                ];
                $data['statusText']['janitor'] = [
                    'en' => $this->translator->trans('ticketClosedBy', [], null, 'en') . ' : ' . $closedBy,
                    'de' => $this->translator->trans('ticketClosedBy', [], null, 'de') . ' : ' . $closedBy,
                ];
                $data['statusText']['owner'] = [
                    'en' => $this->translator->trans('ticketClosedBy', [], null, 'en') . ' : ' . $closedBy,
                    'de' => $this->translator->trans('ticketClosedBy', [], null, 'de') . ' : ' . $closedBy,
                ];
                array_push($responsible, 'property_admin');
                array_push($responsible, 'janitor');
                array_push($responsible, 'owner');
                $companyObj instanceof UserIdentity ? $this->sendNotificationToUser($damage, $data['statusText']['company'], $user, 'company', $companyObj, 'ticketClosedSubject') : '';
            } else if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::TENANT_ROLE)) {
                $data['statusText']['tenant'] = [
                    'en' => $this->translator->trans('ticketClosedBy', [], null, 'en') . ' ' . $closedBy,
                    'de' => $this->translator->trans('ticketClosedBy', [], null, 'de') . ' ' . $closedBy,
                ];
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('ticketClosedBy', [], null, 'en') . ' : ' . $closedBy,
                    'de' => $this->translator->trans('ticketClosedBy', [], null, 'de') . ' : ' . $closedBy,
                ];
                $data['preferredCompany'] = !is_null($companyObj) ? $company['company'] : '';
                array_push($responsible, 'company');
                array_push($responsible, 'tenant');
                $data['statusText']['property_admin'] = [
                    'en' => $this->translator->trans('ticketClosedBy', [], null, 'en') . ' : ' . $closedBy,
                    'de' => $this->translator->trans('ticketClosedBy', [], null, 'de') . ' : ' . $closedBy,
                ];
                $data['statusText']['janitor'] = [
                    'en' => $this->translator->trans('ticketClosedBy', [], null, 'en') . ' : ' . $closedBy,
                    'de' => $this->translator->trans('ticketClosedBy', [], null, 'de') . ' : ' . $closedBy,
                ];
                $data['statusText']['owner'] = [
                    'en' => $this->translator->trans('ticketClosedBy', [], null, 'en') . ' : ' . $closedBy,
                    'de' => $this->translator->trans('ticketClosedBy', [], null, 'de') . ' : ' . $closedBy,
                ];
                array_push($responsible, 'property_admin');
                array_push($responsible, 'janitor');
                array_push($responsible, 'owner');
                $companyObj instanceof UserIdentity ? $this->sendNotificationToUser($damage, $data['statusText']['company'], $user, 'company', $companyObj, 'ticketClosedSubject') : '';
            }
        }
        $data['responsibles'] = $responsible;

        return $data;
    }

    /**
     * @param Damage $damage
     * @param UserIdentity $user
     * @return array
     */
    public function logAppointmentConfirmedDetails(Damage $damage, UserIdentity $user): array
    {
        $data = [];
        $confirmedBy = $this->getUserName($user);
        $company = $this->em->getRepository(DamageOffer::class)->findCompanyWithOffer($damage);
        $companyObj = $this->em->getRepository(UserIdentity::class)->findOneBy(['identifier' => $company['company']]);
        $data['statusText']['company'] = [
            'en' => $this->translator->trans('ticketConfirmedBy', [], null, 'en') . ' : ' . $confirmedBy,
            'de' => $this->translator->trans('ticketConfirmedBy', [], null, 'de') . ' : ' . $confirmedBy,
        ];
        $this->sendNotificationToUser($damage, $data['statusText']['company'], $user, 'company', $companyObj, 'ticketConfirmedSubject');

        return $data;
    }

    /**
     * @param Damage $damage
     * @param UserIdentity $user
     * @return array
     */
    public function logAppointmentRejectDetails(Damage $damage, UserIdentity $user): array
    {
        $data = [];
        $confirmedBy = $this->getUserName($user);
        $company = $this->em->getRepository(DamageOffer::class)->findCompanyWithOffer($damage);
        $companyObj = $this->em->getRepository(UserIdentity::class)->findOneBy(['identifier' => $company['company']]);
        $data['statusText']['company'] = [
            'en' => $this->translator->trans('ticketRejectedBy', [], null, 'en') . ' : ' . $confirmedBy,
            'de' => $this->translator->trans('ticketRejectedBy', [], null, 'de') . ' : ' . $confirmedBy,
        ];
        $this->sendNotificationToUser($damage, $data['statusText']['company'], $user, 'company', $companyObj, 'ticketRejectedSubject');

        return $data;
    }

    /**
     * @param Damage $damage
     * @param UserIdentity $user
     * @return array
     */
    public function logDefectRaisedDetails(Damage $damage, UserIdentity $user): array
    {
        $data = $responsible = [];
        if (!is_null($damage->getDamageOwner())) {
            $defectRaisedBy = $this->getUserName($user);
            $company = $this->em->getRepository(DamageOffer::class)->findCompanyWithOffer($damage);
            $companyObj = $this->em->getRepository(UserIdentity::class)->findOneBy(['identifier' => $company['company']]);
            if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::OBJECT_OWNER_ROLE)) {
                $data['statusText']['object_owner'] = [
                    'en' => $this->translator->trans('defectRaised', [], null, 'en') . ' ' . $defectRaisedBy,
                    'de' => $this->translator->trans('defectRaised', [], null, 'de') . ' ' . $defectRaisedBy,
                ];
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('defectRaised', [], null, 'en') . ' ' . $defectRaisedBy,
                    'de' => $this->translator->trans('defectRaised', [], null, 'de') . ' ' . $defectRaisedBy,
                ];
                $data['preferredCompany'] = $company['company'];
                array_push($responsible, 'company');
                array_push($responsible, 'object_owner');
                $this->sendNotificationToUser($damage, $data['statusText']['company'], $user, 'company', $companyObj, 'defectRaisedSubject');
            } else if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::TENANT_ROLE)) {
                $data['statusText']['tenant'] = [
                    'en' => $this->translator->trans('defectRaised', [], null, 'en') . ' ' . $defectRaisedBy,
                    'de' => $this->translator->trans('defectRaised', [], null, 'de') . ' ' . $defectRaisedBy,
                ];
                $data['statusText']['company'] = [
                    'en' => $this->translator->trans('defectRaised', [], null, 'en') . ' ' . $defectRaisedBy,
                    'de' => $this->translator->trans('defectRaised', [], null, 'de') . ' ' . $defectRaisedBy,
                ];
                $data['preferredCompany'] = $company['company'];
                array_push($responsible, 'company');
                array_push($responsible, 'tenant');
                $this->sendNotificationToUser($damage, $data['statusText']['company'], $user, 'company', $companyObj, 'defectRaisedSubject');
            }
            if ($damage->getApartment()->getProperty()->getUser()) {
                $data['statusText']['owner'] = [
                    'en' => $this->translator->trans('defectRaised', [], null, 'en') . ' ' . $defectRaisedBy,
                    'de' => $this->translator->trans('defectRaised', [], null, 'de') . ' ' . $defectRaisedBy,
                ];
                array_push($responsible, 'owner');
                if (is_null($damage->getApartment()->getProperty()->getAdministrator())) {
                    $this->sendNotificationToUser($damage, $data['statusText']['owner'], $user, 'company', $companyObj, 'defectRaisedSubject');
                }
            }
            if ($damage->getApartment()->getProperty()->getAdministrator()) {
                $data['statusText']['property_admin'] = [
                    'en' => $this->translator->trans('defectRaised', [], null, 'en') . ' ' . $defectRaisedBy,
                    'de' => $this->translator->trans('defectRaised', [], null, 'de') . ' ' . $defectRaisedBy,
                ];
                array_push($responsible, 'property_admin');
                if ($damage->getApartment()->getProperty()->getUser() != $damage->getApartment()->getProperty()->getAdministrator()) {
                    $this->sendNotificationToUser($damage, $data['statusText']['property_admin'], $user, 'company', $companyObj, 'defectRaisedSubject');
                }
            }
            if ($damage->getApartment()->getProperty()->getJanitor()) {
                $data['statusText']['janitor'] = [
                    'en' => $this->translator->trans('defectRaised', [], null, 'en') . ' ' . $defectRaisedBy,
                    'de' => $this->translator->trans('defectRaised', [], null, 'de') . ' ' . $defectRaisedBy,
                ];
                array_push($responsible, 'janitor');
            }
        }
        $data['responsibles'] = $responsible;

        return $data;
    }

    /**
     * @param Damage $damage
     * @param UserIdentity $user
     * @return array
     */
    public function logRepairCompletedDetails(Damage $damage, UserIdentity $user): array
    {
        $data = $responsible = [];
        if (!is_null($damage->getDamageOwner())) {
            $companyName = $user->getCompanyName();
            if ($damage->getApartment()->getProperty()->getUser()) {
                $data['statusText']['owner'] = [
                    'en' => $this->translator->trans('repairCompleted', [], null, 'en') . ' ' . $companyName,
                    'de' => $this->translator->trans('repairCompleted', [], null, 'de') . ' ' . $companyName,
                ];
                array_push($responsible, 'owner');
                if (is_null($damage->getApartment()->getProperty()->getAdministrator())) {
                    $this->sendNotificationToUser($damage, $data['statusText']['owner'], $user, 'owner', $damage->getApartment()->getProperty()->getUser(), 'repairCompletedSubject');
                }
            }
            if ($damage->getApartment()->getProperty()->getAdministrator()) {
                $data['statusText']['property_admin'] = [
                    'en' => $this->translator->trans('repairCompleted', [], null, 'en') . ' ' . $companyName,
                    'de' => $this->translator->trans('repairCompleted', [], null, 'de') . ' ' . $companyName,
                ];
                array_push($responsible, 'property_admin');
                $this->sendNotificationToUser($damage, $data['statusText']['property_admin'], $user, 'property_admin', $damage->getApartment()->getProperty()->getAdministrator(), 'repairCompletedSubject');
            }
            if ($damage->getApartment()->getProperty()->getJanitor()) {
                $data['statusText']['janitor'] = [
                    'en' => $this->translator->trans('repairCompleted', [], null, 'en') . ' ' . $companyName,
                    'de' => $this->translator->trans('repairCompleted', [], null, 'de') . ' ' . $companyName,
                ];
                array_push($responsible, 'janitor');
                $this->sendNotificationToUser($damage, $data['statusText']['janitor'], $user, 'janitor', $damage->getApartment()->getProperty()->getJanitor(), 'repairCompletedSubject');
            }
            $data['statusText']['company'] = [
                'en' => $this->translator->trans('repairCompleted', [], null, 'en') . ' ' . $companyName,
                'de' => $this->translator->trans('repairCompleted', [], null, 'de') . ' ' . $companyName,
            ];
            array_push($responsible, 'company');
            $data['preferredCompany'] = $user->getIdentifier();
            if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::OBJECT_OWNER_ROLE)) {
                $data['statusText']['object_owner'] = [
                    'en' => $this->translator->trans('repairCompleted', [], null, 'en') . ' ' . $companyName,
                    'de' => $this->translator->trans('repairCompleted', [], null, 'de') . ' ' . $companyName,
                ];
                array_push($responsible, 'object_owner');
                $this->sendNotificationToUser($damage, $data['statusText']['object_owner'], $user, 'object_owner', $damage->getUser(), 'repairCompletedSubject');
            } else if ($damage->getCreatedByRole()->getRoleKey() == $this->dmsService
                    ->convertCamelCaseString(Constants::TENANT_ROLE)) {
                $data['statusText']['tenant'] = [
                    'en' => $this->translator->trans('repairCompleted', [], null, 'en') . ' ' . $companyName,
                    'de' => $this->translator->trans('repairCompleted', [], null, 'de') . ' ' . $companyName,
                ];
                array_push($responsible, 'tenant');
                $this->sendNotificationToUser($damage, $data['statusText']['tenant'], $user, 'tenant', $damage->getUser(), 'repairCompletedSubject');
            }
        }
        $data['responsibles'] = $responsible;

        return $data;
    }

    /**
     * @param UserIdentity $user
     * @return string|null
     */
    public function getUserName(UserIdentity $user): ?string
    {
        return $user->getFirstName() . ' ' . $user->getLastName();
    }

    /**
     * @param array|null $requestedCompany
     * @return string
     */
    public function getOfferRequestedCompanyDetails(?array $requestedCompany = []): string
    {
        $companies = [];
        foreach ($requestedCompany as $companyId) {
            $company = $this->em->getRepository(UserIdentity::class)->findOneBy(['identifier' => $companyId]);
            $companies[$company->getIdentifier()] = $company->getCompanyName();
        }

        return implode(', ', array_values($companies));
    }

    /**
     * checkRejectedDamage
     *
     * @param Damage $damage
     * @return boolean
     */
    private function checkRejectedDamage(Damage $damage): bool
    {
        $damageStatus = $this->em->getRepository(DamageStatus::class)->findOneByKey(Constants::STATUS['damage_status']['OWNER_REJECTS_THE_OFFER']);
        $damageLog = $this->em->getRepository(DamageLog::class)->findBy(['damage' => $damage, 'status' => $damageStatus]);
        if (!empty($damageLog)) {
            return true;
        }

        return false;
    }

    /**
     * notifyUsers
     *
     * @param Request $request
     * @param Damage $damage
     * @param array $users
     * @param string $templateName
     * @param UserIdentity $currentUser
     * @param array|null $emailData
     *
     * @return void
     */
    private function notifyUsers(Request $request, Damage $damage, array $users, string $templateName, UserIdentity $currentUser, ?array $emailData = null): void
    {
        $subject = $this->getSubject($damage, $templateName, $emailData);
        if ($currentUser != $damage->getUser()) {
            $users[] = $damage->getUser();
        }
        $sentUsers = [];
        foreach ($users as $userObj) {
            if (in_array($userObj->getIdentifier(), $sentUsers)) {
                continue;
            }
            $isCompany = ($damage->getAssignedCompany() === $userObj);
            if (($this->userService->checkSubscriptionIsExpired($userObj) && !$isCompany) || $templateName == 'emailTenantCloseTheDamage' || $templateName == 'emailOwnerCloseTheDamage') {
                continue;
            }
            $userRole = $this->userService->getUserRoleInDamage($userObj, $damage);
            $userLanguage = ($userObj->getLanguage()) ? $userObj->getLanguage() : $this->containerUtility->getLocale($request);
            $mailSubject = $this->translator->trans($subject, array('%{scheduledDate}' => isset($emailData['scheduledDate']) ? $emailData['scheduledDate'] : null, '%{scheduledTime}' => isset($emailData['scheduledTime']) ? $emailData['scheduledTime'] : null), null, $userLanguage);
            if (isset($emailData['currentRole'])) {
                $mailSubject = str_replace('%ROLE%', $this->translator->trans('role_' . $emailData['currentRole']), $mailSubject);
            }
            $emailData['content'] = $this->getMailContent($damage, $templateName, $emailData);
            $this->sendEmail($userObj, $mailSubject, $templateName, $this->formatEmailData($request, $damage, $userObj, $userLanguage, $currentUser, $userRole, $emailData));
            $this->sendPushNotification($userObj, $damage, $mailSubject, $userRole);
            $sentUsers[] = $userObj->getIdentifier();
        }
    }

    /**
     * getSubject
     *
     * @param Damage $damage
     * @param string $templateName
     * @param array|null $emailData
     *
     * @return string
     */
    private function getSubject(Damage $damage, string $templateName, ?array $emailData = []): string
    {
        if ($templateName === 'DamageRegister') {
            $subject = 'GENERIC_DAMAGE_CREATE_MAIL_SUBJECT';
        } elseif ($templateName === 'emailOwnerDamageRegister') {
            $subject = 'damageRegisteredMailSubject';
        } elseif ($templateName === 'emailOwnerCloseTheDamage') {
            $subject = (isset($emailData['currentRole']) && $this->dmsService->convertSnakeCaseString($emailData['currentRole']) === Constants::PROPERTY_ADMIN_ROLE) ? 'propertyAdminClosedDamage' : 'ownerClosedDamage';
        } elseif (strpos($damage->getStatus()->getKey(), 'PROPERTY_ADMIN') !== false) {
            $subject = str_replace('PROPERTY_ADMIN', 'OWNER', $damage->getStatus()->getKey()) . '_MAIL_SUBJECT';
        } else {
            $subject = $damage->getStatus()->getKey() . '_MAIL_SUBJECT';
        }
        if (isset($emailData['with_signature'])) {
            if ($emailData['with_signature'] == 1) {
                $subject = 'WITH_SIGNATURE_' . $subject;
            } else {
                $subject = 'WITHOUT_SIGNATURE_' . $subject;
            }
        }

        return $subject;
    }

    /**
     * getSubject
     *
     * @param Damage $damage
     * @param string $templateName
     * @param array|null $emailData
     *
     * @return string
     */
    private function getMailContent(Damage $damage, string $templateName, ?array $emailData = []): ?string
    {
        $content = null;
        if ($templateName === 'DamageRegister') {
            $content = $this->translator->trans(lcfirst(str_replace('_', '', ucwords($emailData['currentRole'] . 'RegisteredDamage', "_"))));
        }

        return $content;
    }

    /**
     * sendEmail
     *
     * @param UserIdentity $userObj
     * @param string $subject
     * @param string $templateName
     * @param array $emailData
     * @return void
     */
    private function sendEmail(UserIdentity $userObj, string $subject, string $templateName, array $emailData): void
    {
        if (!file_exists(__DIR__ . "/../../templates/Email/Damage/" . str_replace('email', '', $templateName) . '.html.twig')) {
            $templateName = 'emailStatusChange';
        }
        $this->containerUtility->sendEmail($userObj, 'Damage/' . str_replace('email', '', $templateName), $emailData['locale'], $subject, $emailData);
    }

    /**
     * formatEmailData
     *
     * @param Request $request
     * @param array|null $emailData
     * @param Damage $damage
     * @param UserIdentity $userObj
     * @param string $userLanguage
     * @param UserIdentity $currentUser
     * @param string $userRole
     * @return array
     */
    private function formatEmailData(Request $request, Damage $damage, UserIdentity $userObj, string $userLanguage, UserIdentity $currentUser, string $userRole, ?array $emailData = null): array
    {
        $loginPath = $this->parameterBag->get('web_login_url');
        $loginUrl = ($request->headers->get('domain')) ? $request->headers->get('domain') . $loginPath : $loginPath;
        $companyViewDamage = $this->parameterBag->get('damage_view_url_company');
        $damageView = $this->parameterBag->get('damage_view_url');
        $toMail = $userObj->getUser()->getProperty();
        $data = $toMail . '#' . $damage->getId() . '#' . $damage->getApartment()->getId();
        return [
            'userFirstName' => $userObj->getFirstName(),
            'userLastName' => $userObj->getLastName(),
            'role' => ($damage->getAssignedCompany() === $userObj) ? $this->parameterBag->get('user_roles')['company'] : $userRole,
            'loginUrl' => $loginUrl,
            'bpDamage' => $damage,
            'updatedBy' => $currentUser,
            'locale' => $userLanguage,
            'apartment' => $damage->getApartment(),
            'propertyDetails' => $this->translator->trans('propertyDetails', array(), null, $userLanguage),
            'token' => $this->containerUtility
                    ->encryptData($data, true, $this->parameterBag->get('token_expiry_hours')) . '&lang=' . $userLanguage,
            'companyDamageUrl' => ($request->headers->get('domain')) ? $request->headers->get('domain') . $companyViewDamage : $companyViewDamage,
            'damageUrl' => ($request->headers->get('domain')) ? $request->headers->get('domain') . $damageView : $damageView,
            'emailData' => $emailData,
            'user_roles' => ['owner' => $this->parameterBag->get('user_roles')['owner'],
                'tenant' => $this->parameterBag->get('user_roles')['tenant'],
                'company' => $this->parameterBag->get('user_roles')['company']
            ]
        ];
    }

    /**
     * sendPushNotification
     *
     * @param UserIdentity $userObj
     * @param Damage $damage
     * @param string $subject
     * @param string $userRole
     * @param array|null $content
     * @return void
     */
    public function sendPushNotification(UserIdentity $userObj, Damage $damage, string $subject, string $userRole, ?array $content = null): void
    {
        $locale = $userObj->getLanguage() ? $userObj->getLanguage() : Constants::LANGUAGE_CODES[0];
        $deviceIds = $this->userService->getDeviceIds($userObj);
        $messageContent = '#' . sprintf(Constants::DISPLAY_ID_FORMAT, $damage->getIdentifier()) . ' ' . $damage->getTitle() . ' : '
            . (is_array($content) ? $content[$locale] : $content);
        $params = array(
            'damage' => $damage,
            'toUser' => $userObj,
            'message' => '#' . sprintf(Constants::DISPLAY_ID_FORMAT, $damage->getIdentifier()) . ' ' . $damage->getTitle() . ' : '
                . (is_array($content) ? $content['en'] : $content),
            'messageDe' => '#' . sprintf(Constants::DISPLAY_ID_FORMAT, $damage->getIdentifier()) . ' ' . $damage->getTitle() . ' : '
                . (is_array($content) ? $content['de'] : $content),
            'event' => $damage->getStatus()->getKey(),
            'userRole' => $userRole
        );
        $notificationId = $this->saveDamageNotification($params);
        if (!empty($deviceIds)) {
            $notificationParams = array(
                "damageId" => $damage->getPublicId(),
                'apartmentId' => $damage->getApartment()->getPublicId(),
                'userRole' => $userRole,
                "title" => $subject,
                "message" => $messageContent,
                "damageStatus" => $damage->getStatus()->getKey(), 'notificationId' => $notificationId
            );
            $this->containerUtility->sendPushNotification($notificationParams, $deviceIds);
        }
    }

    /**
     * savePushNotification
     *
     * @param array $params
     *
     * @return integer
     */
    public function saveDamageNotification(array $params): string
    {
        $notification = new PushNotification();
        $notification->setDamage($params['damage']);
        $notification->setToUser($params['toUser']);
        $notification->setCreatedAt(new \DateTime());
        $notification->setMessage($params['message']);
        $notification->setMessageDe($params['messageDe']);
        $notification->setEvent($params['event']);
        $notification->setRole($this->em->getRepository(Role::class)->findOneByRoleKey($params['userRole']));
        $this->em->persist($notification);
        $this->em->flush();

        return $notification->getPublicId();
    }

    /**
     * emailCompanyRejectTheDamage
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity $user
     * @return void
     */
    private function emailCompanyRejectTheDamage(Request $request, Damage $damage, string $templateName, UserIdentity $user): void
    {
        $users = [];
        $createdByRole = $this->getRevelantRole($request, $damage, $damage->getUser());
        if (($createdByRole == $this->parameterBag->get('user_roles')['owner']) || ($createdByRole == $this->parameterBag->get('user_roles')['property_admin'])) {
            $users = $this->em->getRepository(PropertyUser::class)->getActiveUsersByRole($damage->getApartment()->getId(), $this->parameterBag->get('user_roles')['tenant']);
        } elseif ($createdByRole == $this->parameterBag->get('user_roles')['tenant']) {
            $owner = $this->propertyService->getPropertyAdminOrOwner($damage->getApartment()->getProperty(), true);
            if ($owner instanceof UserIdentity) {
                $users[] = $owner;
            }
        }
        $users[] = $damage->getUser();
        if (!empty($users)) {
            $this->notifyUsers($request, $damage, $users, $templateName, $user);
        }
    }

    /**
     * emailOwnerRejectDamage
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity $user
     * @return void
     */
    private function emailOwnerCreateDamage(Request $request, Damage $damage, string $templateName, UserIdentity $user, string $currentRole): void
    {
        $this->emailOwnerRejectDamage($request, $damage, $templateName, $user, $currentRole);
    }

    /**
     * emailOwnerRejectDamage
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity $user
     * @param string $currentRole
     * @return void
     */
    private function emailOwnerRejectDamage(Request $request, Damage $damage, string $templateName, UserIdentity $user, string $currentRole): void
    {
        $damage->setDamageOwner($damage->getUser());
        $this->em->flush();
        if (null !== $damage->getPreferredCompany() && $damage->getPreferredCompany()->getUser()->getDeleted()) {
            $templateName = 'emailCompanyDeleted';
        }
        $users = $this->em->getRepository(PropertyUser::class)->getActiveUsersByRole($damage->getApartment()->getId(), $this->parameterBag->get('user_roles')['tenant']);
        if (!empty($users)) {
            $this->notifyUsers($request, $damage, $users, $templateName, $user);
        }
    }

    /**
     * emailOwnerSendToCompanyWithoutOffer
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity $user
     * @param string $currentRole
     * @return void
     */
    private function emailOwnerSendToCompanyWithoutOffer(Request $request, Damage $damage, string $templateName, UserIdentity $user, string $currentRole): void
    {
        $this->emailOwnerSendToCompanyWithOffer($request, $damage, $templateName, $user, $currentRole);
    }

    /**
     * emailOwnerSendToCompanyWithOffer
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity $user
     * @param $currentUserRole
     * @return void
     */
    public function emailOwnerSendToCompanyWithOffer(Request $request, Damage $damage, string $templateName, UserIdentity $user, $currentUserRole): void
    {
        $emailData = [];
        $users = $this->em->getRepository(PropertyUser::class)->getActiveUsersByRole($damage->getApartment()->getId(), $this->parameterBag->get('user_roles')['tenant']);
        if ($damage->getAssignedCompany() instanceof UserIdentity) {
            $users[] = $damage->getAssignedCompany();
        }
        if ($currentUserRole === 'owner' && null !== $damage->getApartment()->getProperty()->getUser()->getAdministrator()) {
            $users[] = $damage->getApartment()->getProperty()->getUser()->getAdministrator();
        }
        $emailData['reEstimate'] = $this->checkRejectedDamage($damage);
        if (!empty($users)) {
            $this->notifyUsers($request, $damage, $users, $templateName, $user, $emailData);
        }
    }

    /**
     * emailTenantSendToCompanyWithOffer
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity|null $assignedCompany
     * @return void
     */
    private function emailTenantSendToCompanyWithOffer(Request $request, Damage $damage, string $templateName, ?UserIdentity $assignedCompany = null): void
    {
        $emailData = [];
        $users[] = $damage->getAssignedCompany();
        $emailData['reEstimate'] = $this->checkRejectedDamage($damage, Constants::STATUS['damage_status']['TENANT_REJECTS_THE_OFFER']);
        if (!empty($users)) {
            $this->notifyUsers($request, $damage, $users, $templateName, $assignedCompany, $emailData);
        }
    }

    /**
     * emailTenantSendToCompanyWithoutOffer
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity|null $assignedCompany
     *
     * @return void
     */
    private function emailTenantSendToCompanyWithoutOffer(Request $request, Damage $damage, string $templateName, ?UserIdentity $assignedCompany = null): void
    {
        $this->emailTenantSendToCompanyWithOffer($request, $damage, $templateName, $assignedCompany);
    }

    /**
     * emailCompanyGiveOfferToOwner
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity|null $user
     * @return void
     */
    private function emailCompanyGiveOfferToOwner(Request $request, Damage $damage, string $templateName, ?UserIdentity $user): void
    {
        $users = [];
        $assignedBy = $damage->getCompanyAssignedBy();
        if ($assignedBy instanceof UserIdentity) {
            $userRole = $this->getRevelantRole($request, $damage, $assignedBy);
            $roleArray = $this->parameterBag->get('user_roles');
            $users[] = (($userRole == $roleArray['owner']) && ($assignedBy->getAdministrator() instanceof UserIdentity)) ? $assignedBy->getAdministrator() : $assignedBy;
        }
        $company = $damage->getAssignedCompany();
        if ($company instanceof UserIdentity) {
            $users[] = $company;
        }
        if (!empty($users)) {
            $this->notifyUsers($request, $damage, $users, $templateName, $user);
        }
    }

    /**
     * emailCompanyGiveOfferToTenant
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity|null $user
     * @return void
     */
    private function emailCompanyGiveOfferToTenant(Request $request, Damage $damage, string $templateName, ?UserIdentity $user): void
    {
        $this->emailCompanyGiveOfferToOwner($request, $damage, $templateName, $user);
    }

    /**
     * emailOwnerRejectsTheOffer
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity|null $user
     * @return void
     */
    private function emailOwnerRejectsTheOffer(Request $request, Damage $damage, string $templateName, ?UserIdentity $user): void
    {
        $comment = $request->get('rejectionReason');
        $this->emailOwnerAcceptsTheOffer($request, $damage, $templateName, $user, $comment);
    }

    /**
     * emailOwnerAcceptsTheOffer
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity|null $user
     * @param string|null $comment
     * @return void
     */
    private function emailOwnerAcceptsTheOffer(Request $request, Damage $damage, string $templateName, ?UserIdentity $user, string $comment = null): void
    {
        $emailData['comment'] = !is_null($comment) ? $comment : '';
        $company = $damage->getAssignedCompany();
        $users = $this->em->getRepository(PropertyUser::class)->getActiveUsersByRole($damage->getApartment()->getId(), $this->parameterBag->get('user_roles')['tenant']);
        if ($company instanceof UserIdentity) {
            $users[] = $company;
        }
        if (!empty($users)) {
            $this->notifyUsers($request, $damage, $users, $templateName, $user, $emailData);
        }
    }

    /**
     * emailTenantRejectsTheOffer
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity|null $user
     * @return void
     */
    private function emailTenantRejectsTheOffer(Request $request, Damage $damage, string $templateName, ?UserIdentity $user): void
    {
        $this->emailTenantAcceptsTheOffer($request, $damage, $templateName, $user);
    }

    /**
     * emailTenantAcceptsTheOffer
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity|null $user
     * @return void
     */
    private function emailTenantAcceptsTheOffer(Request $request, Damage $damage, string $templateName, ?UserIdentity $user): void
    {
        $users = [];
        $company = $damage->getAssignedCompany();
        if ($company instanceof UserIdentity) {
            $users[] = $company;
        }
        if (!empty($users)) {
            $this->notifyUsers($request, $damage, $users, $templateName, $user);
        }
    }

    /**
     * emailOwnerAcceptsDate
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity|null $user
     * @return void
     */
    private function emailOwnerAcceptsDate(Request $request, Damage $damage, string $templateName, ?UserIdentity $user): void
    {
        $this->emailOwnerAcceptsTheOffer($request, $damage, $templateName, $user);
    }

    /**
     * emailTenantAcceptsDate
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity|null $user
     * @return void
     */
    private function emailTenantAcceptsDate(Request $request, Damage $damage, string $templateName, ?UserIdentity $user): void
    {
        $this->emailTenantAcceptsTheOffer($request, $damage, $templateName, $user);
    }

    /**
     * emailCompanyScheduleDate
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity|null $user
     * @return void
     * @throws \Exception
     */
    private function emailCompanyScheduleDate(Request $request, Damage $damage, string $templateName, ?UserIdentity $user): void
    {
        $company = $damage->getAssignedCompany();
        $emailData['scheduledTime'] = '';
        if ($company instanceof UserIdentity) {
            $emailData['companyName'] = $company->getCompanyName();
            $emailData['companyEmail'] = $company->getUser()->getProperty();
            $emailData['companyAddress'] = isset($company->getAddresses()[0]) ? $company->getAddresses()[0] : null;
        }
        $users = $this->em->getRepository(PropertyUser::class)->getActiveUsersByRole($damage->getApartment()->getId(), $this->parameterBag->get('user_roles')['tenant']);
        $users[] = $this->propertyService->getPropertyAdminOrOwner($damage->getApartment()->getProperty(), true);
        $damageAppointment = $this->em->getRepository(DamageAppointment::class)->findOneBy(['damage' => $damage, 'status' => 1]);
        if ($damageAppointment instanceof DamageAppointment) {
            $scheduledTime = $damageAppointment->getScheduledTime()->format('Y-m-d H:i');
            $schedule = $this->generalUtility
                ->getGMTOffsetDate($scheduledTime, $request->headers->get('gmtOffset'), $request->headers->get('isdst'), false)->format('Y-m-d H:i');
            $emailData['scheduledDate'] = explode(' ', $schedule)[0];
            $emailData['scheduledTime'] = explode(' ', $schedule)[1];
        }
        if (!empty($users)) {
            $this->notifyUsers($request, $damage, $users, $templateName, $user, $emailData);
        }
    }

    /**
     * emailTenantCloseTheDamage
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity|null $user
     * @return void
     */
    private function emailTenantCloseTheDamage(Request $request, Damage $damage, string $templateName, ?UserIdentity $user): void
    {
        $users = [];
        $company = $damage->getAssignedCompany();
        $owner = $this->propertyService->getPropertyAdminOrOwner($damage->getApartment()->getProperty(), true);
        if ($company instanceof UserIdentity && $owner instanceof UserIdentity) {
            $users[] = $company;
            $users[] = $owner;
        }
        if (!empty($users)) {
            $this->notifyUsers($request, $damage, $users, $templateName, $user);
        }
    }

    /**
     * emailOwnerCloseTheDamage
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity|null $user
     * @return void
     */
    private function emailOwnerCloseTheDamage(Request $request, Damage $damage, string $templateName, ?UserIdentity $user, ?string $currentRole = null): void
    {
        $users = $this->em->getRepository(PropertyUser::class)->getActiveUsersByRole($damage->getApartment()->getId(), $this->parameterBag->get('user_roles')['tenant']);
        $company = $damage->getAssignedCompany();
        if ($company instanceof UserIdentity) {
            $users[] = $company;
        }
        if (!empty($users)) {
            $this->notifyUsers($request, $damage, $users, $templateName, $user, ['currentRole' => $currentRole]);
        }
    }

    /**
     * emailRepairConfirmed
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity|null $user
     * @return void
     */
    private function emailRepairConfirmed(Request $request, Damage $damage, string $templateName, ?UserIdentity $user): void
    {
        $users = [];
        $company = $damage->getAssignedCompany();

        if ($company instanceof UserIdentity) {
            $users[] = $company;
        }
        if ($damage->getDamageOwner() instanceof UserIdentity) {
            $userRole = $this->getRevelantRole($request, $damage, $damage->getDamageOwner());
            $roleArray = $this->parameterBag->get('user_roles');
            $users[] = (($userRole == $roleArray['owner']) && ($damage->getDamageOwner()->getAdministrator() instanceof UserIdentity)) ? $damage->getDamageOwner()->getAdministrator() : $damage->getDamageOwner();
        }
        $emailData['with_signature'] = $damage->getSignature() == 1 ? 1 : 0;
        if (!empty($users)) {
            $this->notifyUsers($request, $damage, $users, $templateName, $user, $emailData);
        }
    }

    /**
     * emailDefectRaised
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity|null $user
     * @return void
     */
    private function emailDefectRaised(Request $request, Damage $damage, string $templateName, ?UserIdentity $user): void
    {
        $users = [];
        $company = $damage->getAssignedCompany();
        if ($company instanceof UserIdentity) {
            $users[] = $company;
        }
        if (!empty($users)) {
            $this->notifyUsers($request, $damage, $users, $templateName, $user);
        }
    }

    /**
     * emailCompanyAcceptsDamageWithoutOffer
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity|null $user
     * @return void
     */
    private function emailCompanyAcceptsDamageWithoutOffer(Request $request, Damage $damage, string $templateName, ?UserIdentity $user): void
    {
        $users = $this->em->getRepository(PropertyUser::class)->getActiveUsersByRole($damage->getApartment()->getId(), $this->parameterBag->get('user_roles')['tenant']);

        $users[] = $this->propertyService->getPropertyAdminOrOwner($damage->getApartment()->getProperty(), true);
        if (!empty($users)) {
            $this->notifyUsers($request, $damage, $users, $templateName, $user);
        }
    }

    /**
     * emailCompanyAcceptsDamageWithOffer
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity|null $user
     * @return void
     */
    private function emailCompanyAcceptsDamageWithOffer(Request $request, Damage $damage, string $templateName, ?UserIdentity $user): void
    {
        $users = $this->em->getRepository(PropertyUser::class)->getActiveUsersByRole($damage->getApartment()->getId(), $this->parameterBag->get('user_roles')['tenant']);
        $users[] = $this->propertyService->getPropertyAdminOrOwner($damage->getApartment()->getProperty(), true);
        if (!empty($users)) {
            $this->notifyUsers($request, $damage, $users, $templateName, $user);
        }
    }

    /**
     * emailCompanyAcceptsDamageWithOffer
     *
     * @param Request $request
     * @param Damage $damage
     * @param UserIdentity $user
     * @param string|null $type
     * @return string
     */
    private function getRevelantRole(Request $request, Damage $damage, UserIdentity $user, ?string $type = null): string
    {
        $role = '';
        if ($type == 'createdBy') {
            $role = (null !== $damage->getCreatedByRole()) ? $damage->getCreatedByRole()->getRoleKey() : '';
        } elseif ($type == 'assignedBy') {
            $role = (null !== $damage->getCompanyAssignedByRole()) ? $damage->getCompanyAssignedByRole()->getRoleKey() : '';
        }
        if ($role === '') {
            if ($damage->getApartment()->getProperty()->getUser() === $user) {
                $role = Constants::OWNER_ROLE;
            } elseif ($damage->getApartment()->getProperty()->getAdministrator() === $user) {
                $role = $this->camelCaseConverter(Constants::PROPERTY_ADMIN_ROLE);
            } elseif ($damage->getApartment()->getProperty()->getJanitor() === $user) {
                $role = Constants::JANITOR_ROLE;
            }
        }
        if ($role != '') {
            return $role;
        }
        $propertyUser = $this->em->getRepository(PropertyUser::class)->findOneBy(['object' => $damage->getApartment(), 'user' => $user, 'isActive' => true, 'deleted' => false]);
        if (null !== $propertyUser) {
            $role = $propertyUser->getRole()->getRoleKey();
        }

        return $role;
    }

    /**
     * emailOwnerRejectDamage
     *
     * @param Request $request
     * @param Damage $damage
     * @param string $templateName
     * @param UserIdentity $user
     * @param string $currentRole
     * @return void
     */
    private function sendGenericDamageMail(Request $request, Damage $damage, string $templateName, UserIdentity $user, string $currentRole): void
    {
        $users = [];
        if (strpos($damage->getStatus()->getKey(), '_CREATE_DAMAGE') !== false) {
            $templateName = 'DamageRegister';
            if ($user->getDeleted()) {
                $templateName = 'emailCompanyDeleted';
            }
            $users[] = $this->propertyService->getPropertyAdminOrOwner($damage->getApartment()->getProperty(), true);
        }
        if (!empty($users)) {
            $this->notifyUsers($request, $damage, $users, $templateName, $user, ['currentRole' => $currentRole]);
        }
    }

    /**
     * getTicketUsers
     *
     * function to get list of all users related to a ticket
     *
     * @param Damage $damage
     * @param bool $format
     * @param Apartment|null $apartment
     * @return array
     */
    public function getTicketUsers(Damage $damage, ?bool $format = true, ?Apartment $apartment = null): array
    {
        $userList = [];
        if (null === $apartment) {
            $apartment = $damage->getApartment();
        }
        $users = $this->em->getRepository(PropertyUser::class)->getActiveUsers($apartment->getId());
        $users[] = $apartment->getProperty()->getUser();
        if (null !== $apartment->getProperty()->getAdministrator()) {
            $users[] = $apartment->getProperty()->getAdministrator();
        }
        if (null !== $apartment->getProperty()->getJanitor()) {
            $users[] = $apartment->getProperty()->getJanitor();
        }
        if (null !== $damage->getAssignedCompany()) {
            $users[] = $damage->getAssignedCompany();
            $companyUsers = $this->em->getRepository(UserIdentity::class)->getActiveCompanyUsers($damage->getAssignedCompany());
            $users += $this->getUser($companyUsers);
        }
        $requestedCompanies = $this->getRequestedCompanies($damage, true);
        if (!empty($requestedCompanies)) {
            foreach ($requestedCompanies as $requestedCompany) {
                $users[] = $requestedCompany;
            }
        }

        foreach (array_filter($users) as $userIdentity) {
            if ($format) {
                $details = $this->userService->getUserData($userIdentity);
                $details['publicId'] = $userIdentity->getPublicId();
                $details['companyName'] = $userIdentity->getCompanyName();
                $details['deviceId'] = $this->userService->getDeviceIds($userIdentity);
                $details['apartment']['publicId'] = (string)$apartment->getPublicId();
                $details['apartment']['name'] = $apartment->getName();
                $userList[$userIdentity->getId()] = $details;
            } else {
                $userList[$userIdentity->getId()] = $userIdentity;
            }
        }
        $tmp = [];
        foreach ($userList as $key => $value) {
            if (!in_array($key, $tmp)) {
                $tmp[] = $key;
            } else {
                unset($userList[$key]);
            }
        }

        return $userList;
    }

    /**
     * getFormOptions
     *
     * function to get Form Options
     *
     * @param Request $request
     * @param string $currentRole
     * @param UserIdentity $user
     * @return array
     */
    public function getFormOptions(Request $request, string $currentRole, UserIdentity $user): array
    {
        $options['validation_groups'] = ['default'];
        if ((null !== $request->get('status') && strpos($request->get('status'), 'SEND_TO_COMPANY')) || (null === $request->get('status') && strpos($this->getInitialDamageStatus($request, $currentRole, $user), 'SEND_TO_COMPANY'))) {
            $options['validation_groups'][] = 'sendToCompany';
        } elseif ($request->get('status') === 'COMPANY_SCHEDULE_DATE') {
            $options['validation_groups'][] = 'scheduledDate';
        } elseif ($request->get('status') === 'REPAIR_CONFIRMED') {
            $options['validation_groups'][] = 'repairConfirmed';
            if ($request->get('withSignature')) {
                $options['validation_groups'][] = 'repairConfirmedWithSignature';
            }
        } elseif ($request->get('status') === 'DEFECT_RAISED') {
            $options['validation_groups'][] = 'defectRaised';
        }

        return $options;
    }

    /**
     * markAsRead
     *
     * function to mark damage as read
     *
     * @param Damage $damage
     * @param UserIdentity $user
     * @return void
     */
    public function markAsRead(Damage $damage, UserIdentity $user): void
    {
        $damage->addReadUser($user);
        $this->em->flush();

        return;
    }

    /**
     * Get damage images and add them to the damage detail array.
     *
     * @param Request $request The current request object
     * @param Damage $damage The Damage entity
     * @param array $damageDetail The damage detail array to update
     * @param bool|null $encode Whether to encode the image data or not
     *
     * @return void
     * @throws \Exception
     */
    private function getDamageImages(Request $request, Damage $damage, array &$damageDetail, ?bool $encode = true): void
    {
        $images = $this->em->getRepository(DamageImage::class)->findBy(['damage' => $damage, 'deleted' => 0]);
        $imageCategoryMap = $this->getImageCategoryMap();

        foreach ($images as $image) {
            $imageCategory = $image->getImageCategory();
            if (isset($imageCategoryMap[$imageCategory])) {
                $key = $imageCategoryMap[$imageCategory];
                $damageDetail[$key][] = $this->fileUploadHelper->getDamageFileInfo($image, $request->getSchemeAndHttpHost(), $encode);
            }
        }
    }

    /**
     * Get the map of image categories to damage detail keys.
     *
     * @return array The map of image categories to damage detail keys
     */
    private function getImageCategoryMap(): array
    {
        return [
            $this->parameterBag->get('image_category')['floor_plan'] => 'locationImage',
            $this->parameterBag->get('image_category')['bar_code'] => 'barCodeImage',
            $this->parameterBag->get('image_category')['photos'] => 'damageImages',
            $this->parameterBag->get('image_category')['confirm'] => 'signature',
            $this->parameterBag->get('image_category')['offer_doc'] => 'signature',
        ];
    }

    /**
     * @param Request $request
     * @param $attachment
     * @param array $damageDetail
     * @param bool|null $encode
     * @return array
     * @throws \Exception
     */
    private function getDamageOfferImages(Request $request, $attachment, array &$damageDetail, ?bool $encode = true): array
    {
        $images = $this->em->getRepository(DamageImage::class)->findBy(['identifier' => $attachment, 'deleted' => 0]);
        foreach ($images as $image) {
            switch ($image->getImageCategory()) {
                case $this->parameterBag->get('image_category')['floor_plan']:
                    $damageDetail['locationImage'][] = $this->fileUploadHelper
                        ->getDamageFileInfo($image, $request->getSchemeAndHttpHost(), false);
                    break;
                case $this->parameterBag->get('image_category')['bar_code']:
                    $damageDetail['barCodeImage'] = $this->fileUploadHelper
                        ->getDamageFileInfo($image, $request->getSchemeAndHttpHost(), $encode);
                    break;
                case $this->parameterBag->get('image_category')['photos']:
                    $damageDetail['damageImages'][] = $this->fileUploadHelper
                        ->getDamageFileInfo($image, $request->getSchemeAndHttpHost(), $encode);
                    break;
                case $this->parameterBag->get('image_category')['confirm']:
                    $damageDetail['signature'] = $this->fileUploadHelper
                        ->getDamageFileInfo($image, $request->getSchemeAndHttpHost(), $encode);
                    break;
                case $this->parameterBag->get('image_category')['offer_doc']:
                    $damageDetail['offerDoc'] = $this->fileUploadHelper
                        ->getDamageFileInfo($image, $request->getSchemeAndHttpHost(), false);
                    break;
                default:
                    break;
            }
        }

        return $damageDetail;
    }

    /**
     * getPermissions
     *
     * function to get permissible actions
     *
     * @param Damage $damage
     * @param UserIdentity $user
     * @param string|null $role
     * @return array
     */
    private function getPermissions(Damage $damage, UserIdentity $user, ?string $role = null): array
    {
        $return = [];
        $users = $this->getTicketUsers($damage, false);
        $users[] = $damage->getUser();
        $status = $damage->getStatus()->getKey();
        $allRoles = $this->parameterBag->get('user_roles');
        if (in_array($user, $users) && !$this->isClosedStatus($status)) {
            $isCompany = ($user === $damage->getAssignedCompany());
            $propertyLevelRoles = [$allRoles['property_admin'], $allRoles['owner'], $allRoles['janitor']];
            $showEditUser = ($damage->getUser() === $user || in_array($role, [$allRoles['owner'], $allRoles['property_admin']]));
            $isObjectLevelUser = in_array($role, [$allRoles['tenant'], $allRoles['object_owner']]);
            $isPropertyLevelUser = in_array($role, $propertyLevelRoles);
            $companyAssignedByPropertyLevelUser = (null !== $damage->getCompanyAssignedByRole()) ? in_array($damage->getCompanyAssignedByRole()->getRoleKey(), $propertyLevelRoles) : true;
            $showEdit = (($status === 'COMPANY_REJECT_THE_DAMAGE') || (strpos($status, 'REJECTS_THE_OFFER') !== false));
            if ($isPropertyLevelUser && $companyAssignedByPropertyLevelUser && $showEditUser && $showEdit) {
                $return[] = 'edit';
            }
            if ($isCompany && strpos($status, 'REJECTS_THE_OFFER') === false) {
                $return[] = 'edit';
            }
            if (!$isCompany && $showEditUser) {
                $return[] = 'delete';
            }
            if ($isObjectLevelUser && (strpos($status, 'REJECT_DAMAGE') !== false || ($showEdit && !$companyAssignedByPropertyLevelUser)) && $damage->getUser() === $user) {
                $return[] = 'edit';
                $return[] = 'sendToCompany';
            }
        }

        return $return;
    }

    /**
     * function to get Damage Status Array
     *
     * @param string|null $type || null
     * @return array
     */
    public function getDamageStatusArray(?string $type = null): array
    {
        $return = [];
        $damageStatusArray = $this->em->getRepository(DamageStatus::class)->findBy(['active' => 1, 'deleted' => 0]);
        foreach ($damageStatusArray as $damageStatus) {
            if ($type === null || ($type === 'open' && !$this->isClosedStatus($damageStatus->getKey())) || ($type === 'closed' && $this->isClosedStatus($damageStatus->getKey())) ||
                ($type === 'withCompany' && str_contains($damageStatus->getKey(), 'SEND_TO_COMPANY')) ||
                ($type === 'unresponsive' && (str_contains($damageStatus->getKey(), 'COMPANY_ACCEPTS_DAMAGE') || str_contains($damageStatus->getKey(), '_CREATE_DAMAGE') || str_contains($damageStatus->getKey(), 'COMPANY_GIVE_OFFER_TO') || str_contains($damageStatus->getKey(), 'ACCEPTS_THE_OFFER') || $damageStatus->getKey() == 'COMPANY_SCHEDULE_DATE'))
            ) {
                $return[] = $damageStatus->getKey();
            }
        }

        return $return;
    }

    /**
     * function to find if a status is in closed catagory
     *
     * @param string $status
     * @return bool
     */
    private function isClosedStatus(string $status): bool
    {
        return (strpos($status, '_CLOSE_THE_DAMAGE') !== false);
    }

    /**
     * get DamageList
     *
     * function to get list of damage tickets filtered using free text
     *
     * @param Request $request
     * @param UserIdentity $user
     * @param $currentRole
     * @return array
     * @throws FormErrorException
     * @throws \Exception
     */
    public function getFilteredDamageList(Request $request, UserIdentity $user, string $currentRole): array
    {
        if ($request->request->get('text') !== '' && strlen(trim($request->request->get('text'))) < Constants::MIN_SEARCH_CHARACHERS) {
            throw new FormErrorException('minimumCharactersRequiredForSearch');
        }
        $param['apartment'] = false;
        $param['property'] = false;
        $param['offset'] = $request->request->get('offset');
        $param['limit'] = $request->request->get('limit');
        $param['text'] = $request->request->get('text') !== '' ? $request->request->get('text') : null;
        $param['status'] = !empty($request->request->get('status')) ? $request->request->get('status') : 'open';

        if ($request->request->has('property') && $request->request->get('property') !== '') {
            $property = $this->em->getRepository(Property::class)->findOneBy(['publicId' => $request->request->get('property')]);
            if ($property instanceof Property) {
                $param['property'] = $property->getIdentifier();
            }
        }
        $damages = $this->em->getRepository(Damage::class)->getAllDamages($user, $currentRole, $param);
        return $this->getFormattedDamageList($damages, $request, $user, $currentRole);
    }

    /**
     * getTicketLocationDetails
     *
     * function to generate ticket details array
     *
     * @param Damage $damage
     * @param Request $request
     * @param UserIdentity $user
     * @return array
     * @throws \Exception
     */
    public function getTicketLocationDetails(Damage $damage, Request $request, UserIdentity $user): array
    {
        $damageDetail = [];
        $images = $this->em->getRepository(DamageImage::class)->findBy(['damage' => $damage, 'imageCategory' => $this->parameterBag->get('image_category')['floor_plan'], 'deleted' => 0]);
        foreach ($images as $image) {
            $damageDetail['locationImage'][] = $this->fileUploadHelper->getDamageFileInfo($image, $request->getSchemeAndHttpHost(), true);
        }

        return $damageDetail;
    }

    /**
     * getDamageImages
     *
     * function to generate ticket images
     *
     * @param Request $request
     * @param Damage $damage
     * @param array $damageDetail
     * @return void
     *
     * @throws \Exception
     */
    private function getDamagePhotos(Request $request, Damage $damage, array &$damageDetail): void
    {
        $images = $this->em->getRepository(DamageImage::class)->findBy(['damage' => $damage, 'deleted' => 0, 'imageCategory' => $this->parameterBag->get('image_category')['photos']]);
        foreach ($images as $image) {
            $damageDetail['damageImages'][] = $this->fileUploadHelper->getDamageFileInfo($image, $request->getSchemeAndHttpHost(), false);
        }
    }

    /**
     * Set the current user for the damage based on the status key.
     *
     * @param Damage $damage The Damage entity
     * @param string $newStatus
     */
    private function setCurrentUser(Damage $damage, string $newStatus): void
    {
//        $owner = $this->propertyService->getPropertyAdminOrOwner($damage->getApartment()->getProperty());
//        $role = null;
//        if (in_array($newStatus, ['TENANT_CREATE_DAMAGE', 'OBJECT_OWNER_CREATE_DAMAGE', 'JANITOR_CREATE_DAMAGE', 'COMPANY_GIVE_OFFER_TO_PROPERTY_ADMIN', 'COMPANY_GIVE_OFFER_TO_OWNER'])) {
//            //Owner/Admin has to assign a company
//            $damage->setCurrentUser($owner['user']);
//            $role = $this->containerUtility->getRoleByKey($owner['role']);
//            $damage->setCurrentUserRole($role);
//        } elseif (str_contains($newStatus, 'SEND_TO_COMPANY') ||
//            str_contains($newStatus, 'REJECTS_DATE') ||
//            str_contains($newStatus, 'ACCEPTS_DATE') ||
//            str_contains($newStatus, 'THE_OFFER') ||
//            str_contains($newStatus, 'COMPANY_ACCEPTS_DAMAGE') ||
//            str_contains($newStatus, 'DEFECT_RAISED')) {
//            //Company has to take action
//            $damage->setCurrentUser($damage->getAssignedCompany());
//            $role = $this->containerUtility->getRoleByKey('company');
//            $damage->setCurrentUserRole($role);
//        } elseif (str_contains($newStatus, 'REJECT_DAMAGE')) {
//            //Damage created user has to take action when owner/admin rejects damage
//            $damage->setCurrentUser($damage->getUser()); //createdbyrole
//            $damage->setCurrentUserRole($damage->getCreatedByRole());
//        } elseif (
//            str_contains($newStatus, 'COMPANY_SCHEDULE_DATE') ||
//            str_contains($newStatus, 'COMPANY_REJECT_THE_DAMAGE') ||
//            str_contains($newStatus, 'GIVE_OFFER_TO') ||
//            str_contains($newStatus, 'REPAIR_CONFIRMED')) {
//            //User who assigned the Company has to take action
//            $damage->setCurrentUser($damage->getCompanyAssignedBy())
//                ->setCurrentUserRole($damage->getCompanyAssignedByRole());
//        } else {
//            //No action needs to be taken
//            $damage->setCurrentUser(null)
//                ->setCurrentUserRole(null);
//        }

        $owner = $this->propertyService->getPropertyAdminOrOwner($damage->getApartment()->getProperty());
        $statusActions = [
            ['statuses' => ['TENANT_CREATE_DAMAGE', 'OBJECT_OWNER_CREATE_DAMAGE', 'JANITOR_CREATE_DAMAGE', 'COMPANY_GIVE_OFFER_TO_PROPERTY_ADMIN', 'COMPANY_GIVE_OFFER_TO_OWNER'],
                'action' => function () use ($damage, $owner) {
                    $damage->setCurrentUser($owner['user']);
                    $role = $this->containerUtility->getRoleByKey($owner['role']);
                    $damage->setCurrentUserRole($role);
                }],
            ['statuses' => ['SEND_TO_COMPANY', 'REJECTS_DATE', 'ACCEPTS_DATE', 'THE_OFFER', 'COMPANY_ACCEPTS_DAMAGE', 'DEFECT_RAISED'],
                'action' => function () use ($damage) {
                    $damage->setCurrentUser($damage->getAssignedCompany());
                    $role = $this->containerUtility->getRoleByKey('company');
                    $damage->setCurrentUserRole($role);
                }],
            ['statuses' => ['REJECT_DAMAGE'],
                'action' => function () use ($damage) {
                    $damage->setCurrentUser($damage->getUser());
                    $damage->setCurrentUserRole($damage->getCreatedByRole());
                }],
            ['statuses' => ['COMPANY_SCHEDULE_DATE', 'COMPANY_REJECT_THE_DAMAGE', 'GIVE_OFFER_TO', 'REPAIR_CONFIRMED'],
                'action' => function () use ($damage) {
                    $damage->setCurrentUser($damage->getCompanyAssignedBy())
                        ->setCurrentUserRole($damage->getCompanyAssignedByRole());
                }]
        ];

        $actionTaken = false;
        foreach ($statusActions as $statusAction) {
            foreach ($statusAction['statuses'] as $status) {
                if (str_contains($newStatus, $status)) {
                    $statusAction['action']();
                    $actionTaken = true;
                    break 2;
                }
            }
        }

        if (!$actionTaken) {
            $damage->setCurrentUser(null)
                ->setCurrentUserRole(null);
        }
    }

    /**
     * @param array $damages
     * @param Request $request
     * @param UserIdentity $user
     * @param string|null $currentRole
     * @return array
     * @throws \Exception
     */
    private function getFormattedDamageList(array $damages, Request $request, UserIdentity $user, ?string $currentRole = null): array
    {
        $data = [];
        $propertyOwners = $this->em->getRepository(Property::class)->findPropertyOwners($user->getIdentifier());
        if (!is_null($currentRole) && $this->snakeToCamelCaseConverter($currentRole) === Constants::PROPERTY_ADMIN_ROLE) {
            $janitors = $this->em->getRepository(Property::class)->findPropertyJanitorsForPropertyAdmins($user->getIdentifier());
            $propertyOwners = array_merge($janitors, $propertyOwners);
        }
        array_push($propertyOwners, $user->getIdentifier());
        foreach ($damages as $damage) {
            if (!is_null($currentRole) && $this->snakeToCamelCaseConverter($currentRole) === Constants::PROPERTY_ADMIN_ROLE &&
                (!in_array($damage->getDamageOwner()->getIdentifier(), $propertyOwners) && !$damage->getAllocation())) {
                continue;
            }
            $data[] = $this->generateDamageDetails($damage, $request, $user, true, $currentRole);
        }
        return $data;
    }

    /**
     *
     * @param array $companyUsers
     * @return array
     */
    public function getUser(array $companyUsers): array
    {
        $users = [];
        foreach ($companyUsers as $companyUser) {
            $users[] = $this->em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $companyUser['publicId'], 'deleted' => false]);
        }

        return $users;
    }

    /**
     * @param Request $request
     * @param string $userLanguage
     * @param string $userRole
     * @return bool
     */
    public function sendEmailNotification(Request $request, string $userLanguage, string $userRole): bool
    {
        $damage = $this->em->getRepository(Damage::class)->findOneBy(['publicId' => $request->get('damage')]);
        foreach ($request->get('company') as $company) {
            $userObj = $this->em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $company]);
            $this->emailOwnerSendToCompanyWithOffer($request, $damage, 'Damage/OwnerSendToCompanyWithoutOffer', $userObj, $userRole);
        }

        return true;
    }

    /**
     * @param Category|null $issueType
     * @return array
     */
    public function getFormattedIssueType(?Category $issueType): array
    {
        $data['publicId'] = $issueType->getPublicId();
        $data['name'] = $issueType->getName();
        return $data;
    }

    /**
     * getRequestedCompanies
     *
     * @param Damage $damage
     * @param bool $isReturnObject
     * @return array
     */
    private function getRequestedCompanies(Damage $damage, bool $isReturnObject = false): array
    {
        $requestedCompanies = $this->em->getRepository(DamageRequest::class)->findBy(['damage' => $damage, 'deleted' => false]);
        $companies = [];
        foreach ($requestedCompanies as $requestedCompany) {
            if ($requestedCompany->getCompany() instanceof UserIdentity) {
                if ($isReturnObject) {
                    $companies[] = $requestedCompany->getCompany();
                } else {
                    $companies[] = $requestedCompany->getCompany()->getIdentifier();
                }
            }
        }
        return $companies;
    }

    /**
     * damageDetails
     *
     * @param Request $request
     * @param Damage $damage
     * @param Property $property
     * @param UserIdentity|null $user
     * @param string|null $currentUserRole
     * @param bool|null $isList
     * @return array
     * @throws \Exception
     */
    private function damageDetails(Request $request, Damage $damage, Property $property,
                                   ?UserIdentity $user = null, string $currentUserRole = null, ?bool $isList = false): array
    {
        $requestedUser = null;
        $requestType = $request->get('type');
        $damageDetail['ticketNumber'] = '#' . sprintf(Constants::DISPLAY_ID_FORMAT, $damage->getIdentifier());
        $damageDetail['publicId'] = $damage->getPublicId();
        $damageDetail['title'] = $damage->getTitle();
        if ($user instanceof UserIdentity) {
            $damageDetail['isExpired'] = $user->getIsExpired();
            if (!is_null($currentUserRole) && in_array($this->snakeToCamelCaseConverter($currentUserRole), [Constants::COMPANY_ROLE, Constants::GUEST_ROLE, Constants::COMPANY_USER_ROLE])) {
                $requestedUser = in_array($currentUserRole, [Constants::COMPANY_ROLE, Constants::GUEST_ROLE]) ? $user : $user->getParent();
            }
        }
        $damageDetail['status'] = $damage->getStatus()->getKey();
        $damageDetail['apartmentName'] = $damage->getApartment()->getName();
        $damageDetail['propertyName'] = $property->getAddress();
        $damageDetail['isPropertyActive'] = $property->getActive();
        $damageDetail['cancelledOrExpired'] = $this->propertyService->checkPropertyCancelledOrExpired($property);
        $damageDetail['isApartmentActive'] = $damage->getApartment()->getActive();
        $damageDetail['updatedAt'] = $damage->getUpdatedAt();
        $damageDetail['createdBy'] = [
            'publicId' => $damage->getUser()->getPublicId(),
            'firstName' => $damage->getUser()->getFirstName(),
            'lastName' => $damage->getUser()->getLastName()
        ];
        $damageDetail['createdAt'] = $damage->getCreatedAt();
        $damageDetail['createdBy']['role'] = $this->getRevelantRole($request, $damage, $damage->getUser(), 'createdBy');
        $damageDetail['companyName'] = (null !== $damage->getAssignedCompany()) ?
            $damage->getAssignedCompany()->getFirstName() . ' ' . $damage->getAssignedCompany()->getLastName() : null;
        $damageDetail['companyAssignedBy'] = (null !== $damage->getCompanyAssignedBy()) ?
            [
                'publicId' => $damage->getCompanyAssignedBy()->getPublicId(),
                'firstName' => $damage->getCompanyAssignedBy()->getFirstName(),
                'lastName' => $damage->getCompanyAssignedBy()->getLastName(),
                'role' => $this->getRevelantRole($request, $damage, $damage->getCompanyAssignedBy(), 'assignedBy')
            ] : null;
        if (!$isList || (!empty($requestType) && ($requestType == 'dashboard'))) {
            $offerRequestDetails = $this->getFormattedDamageRequests($damage, $request, $requestedUser);
            if (isset($offerRequestDetails['offerRequestCount'])) {
                $damageDetail['offerRequestCount'] = $offerRequestDetails['offerRequestCount'];
                unset($offerRequestDetails['offerRequestCount']);
            }
            $damageDetail['requestedCompanyDetails'] = $offerRequestDetails;
            !$isList ? $this->getDamageAppointments($damage, $request, $damageDetail, $isList) : true;
        }
        return $damageDetail;
    }

    /**
     * generate damage log details
     *
     * @param Damage $damage
     * @param string $currentUserRole
     * @param UserIdentity $currentUser
     * @return array
     */
    public function generateDamageLog(Damage $damage, string $currentUserRole, UserIdentity $currentUser): array
    {
        if ($currentUserRole == Constants::GUEST_ROLE) {
            $currentUserRole = Constants::COMPANY_ROLE;
        }
        $damageLogs = $this->em->getRepository(DamageLog::class)->getDamageLogDetails($damage->getIdentifier());
        $arrayResult = [];
        $language = $currentUser->getLanguage();
        if ($this->snakeToCamelCaseConverter($currentUserRole) == Constants::COMPANY_USER_ROLE) {
            $currentUserRole = Constants::COMPANY_ROLE;
            $language = $currentUser->getLanguage();
            $currentUser = $currentUser->getParent();
        }
        if (!empty($damageLogs)) {
            foreach ($damageLogs as $result) {
                if (isset($result['statusText'][$currentUserRole])) {
                    if ($currentUserRole == Constants::COMPANY_ROLE && $currentUser->getIdentifier() != $result['preferredCompany']
                        && !in_array($currentUserRole . $currentUser->getIdentifier(), array_keys($result['statusText']))) {
                        continue;
                    }
                    $data['publicId'] = $result['publicId'];
                    $data['statusDescription'] = $result['description'];
                    if (in_array($currentUserRole . $currentUser->getIdentifier(), array_keys($result['statusText']))) {
                        $data['description'] = $result['statusText'][$currentUserRole . $currentUser->getIdentifier()][$language] ?? '';
                    } else {
                        $data['description'] = $result['statusText'][$currentUserRole][$language] ?? '';
                    }
                    $data['status'] = $result['status'];
                    $data['createdAt'] = $result['createdAt'];
                    $data['responsibles'] = $result['responsibles'];
                    array_push($arrayResult, $data);
                    unset($data);
                }
            }
        }

        return $arrayResult;
    }

    /**
     * @param Collection $offers
     * @return UserIdentity|null
     */
    public function getOfferAcceptedCompany(Collection $offers): ?UserIdentity
    {
        foreach ($offers as $offer) {
            if (!is_null($offer->getAcceptedDate())) {
                return $offer->getCompany();
            }
        }
        return null;
    }

    /**
     * getDamageAppointments
     *
     * @param Damage $damage
     * @param Request $request
     * @param $damageDetail
     * @param bool|null $encodedData
     * @throws \Exception
     */
    private function getDamageAppointments(Damage $damage, Request $request, &$damageDetail, ?bool $encodedData = true)
    {
        $damageAppointment = $this->em->getRepository(DamageAppointment::class)->findOneBy(['damage' => $damage->getIdentifier()], ['createdAt' => 'DESC']);
        if ($damageAppointment instanceof DamageAppointment) {
            $damageDetail['appointmentDate'] = $damageAppointment->getScheduledTime();
            $damageDetail['appointmentDateFormatted'] = $damageAppointment->getScheduledTime()->format('d.m.Y');
            $damageDetail['appointmentTimeFormatted'] = $damageAppointment->getScheduledTime()->format('H:i');
        }
        $damageDetail['originalLocationImages'] = $this->getOriginalFloorPlanImages($damage->getApartment(), $request, $encodedData);
        if ($damage->getAssignedCompany() instanceof UserIdentity) {
            $damageDetail['assignedCompany'] = $this->companyService->getCompanyDetailArray($damage->getAssignedCompany());
            $damageDetail['expiryDate'] = $damage->getAssignedCompany()->getExpiryDate();
        }
    }

    /**
     * saveNonRegisteredUsersDamageRequest
     *
     * @param array $request
     * @param UserIdentity $user
     * @param string $currentRole
     * @param string $locale
     * @return bool
     * @throws \Exception
     */
    public function saveNonRegisteredUsersDamageOfferRequest(array $request, UserIdentity $user, string $currentRole, string $locale): bool
    {
//        $companies = [];
        $damage = $this->em->getRepository(Damage::class)->findOneBy(['publicId' => $request['damage']]);
        $emails = explode(';', $request['email']);
//        $status = $this->em->getRepository(DamageStatus::class)->findOneBy(['key' => $request['status']]);
        foreach (array_unique($emails) as $company) {
//            $damageRequest = $this->em->getRepository(DamageRequest::class)->findDamageRequest(
//                ['damage' => $damage->getIdentifier(), 'company' => $user->getIdentifier(), 'companyEmail' => $company]);
//            if ($damageRequest instanceof DamageRequest) {
//                $damageRequest->setDeleted(true);
//            }
            if (filter_var($company, FILTER_VALIDATE_EMAIL) && $damage instanceof Damage) {
//                $damageRequest = new DamageRequest();
//                $damageRequest->setDamage($damage);
//                $damageRequest->setRequestedDate(new \DateTime('now'));
//                $damageRequest->setCompanyEmail($company);
                $this->companyService->sendNonRegisteredCompanyEmailNotification($company, $damage, $locale,
                    $request['subject'], $this->getPortalUrl($damage->getPublicId()));
//                $damageRequest->setStatus($status);
//                $this->em->persist($damageRequest);
//                $this->em->flush();
            }
        }
//        $this->em->flush();
//        $damage->setStatus($status);
//        $this->logDamage($user, $damage, null, null, null, $companies);
//        $damage->setCompanyAssignedBy($user);
//        $damage->setCompanyAssignedByRole($this->em->getRepository(Role::class)->findOneBy(['roleKey' => $currentRole]));
//        $this->em->flush();

        return true;
    }

    /**
     * @param array $params
     * @return array
     */
    public function checkRequestAlreadyInitiated(array $params = []): array
    {
        $damageObj = $this->em->getRepository(Damage::class)->findOneBy(['publicId' => $params['damage']]);
        foreach ($params['company'] as $key => $company) {
            $requestedCompany = $this->em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $company, 'deleted' => false]);
            $alreadyRequested = $this->em->getRepository(DamageRequest::class)
                ->findOneBy(['damage' => $damageObj, 'company' => $requestedCompany, 'deleted' => false, 'status' => null]);
            if ($alreadyRequested instanceof DamageRequest) {
                unset($params['company'][$key]);
            }
        }

        return $params;
    }

    /**
     * getPortalUrl
     *
     * @param string $damage
     * @return string
     */
    public function getPortalUrl(string $damage): ?string
    {
        $portalUrl = $this->parameterBag->has('portal_url') ? $this->parameterBag->get('portal_url') : null;
        if (!is_null($portalUrl)) {
            return sprintf($portalUrl, $damage);
        }
        return null;
    }

    /**
     * registerDamageRequestIfNotExists
     *
     * @param string $damage
     * @param UserIdentity $user
     * @param string|null $email
     * @return bool
     */
    public function registerDamageRequestIfNotExists(string $damage, UserIdentity $user, ?string $email = null): bool
    {
        $damage = $this->em->getRepository(Damage::class)->findOneBy(['publicId' => $damage]);
        $damageRequest = $this->em->getRepository(DamageRequest::class)->findDamageRequest(
            ['damage' => $damage->getIdentifier(), 'company' => $user->getIdentifier(), 'companyEmail' => $email]);
        if (!$damageRequest instanceof DamageRequest && $damage instanceof Damage) {
            $assignedBy = $damage->getUser();
            if ($damage->getCreatedByRole()->getRoleKey() === Constants::OWNER_ROLE &&
                $damage->getApartment()->getProperty()->getAdministrator() instanceof UserIdentity) {
                $assignedBy = $damage->getApartment()->getProperty()->getAdministrator();
            } elseif (in_array($damage->getCreatedByRole()->getRoleKey(), [Constants::TENANT_ROLE, Constants::OBJECT_OWNER_ROLE]) && $damage->getAllocation()) {
                if ($damage->getApartment()->getProperty()->getAdministrator() instanceof UserIdentity) {
                    $assignedBy = $damage->getApartment()->getProperty()->getAdministrator();
                } else {
                    $assignedBy = $damage->getApartment()->getProperty()->getUser();
                }
            }
            $key = strtoupper($damage->getCompanyAssignedByRole()->getRoleKey()) . '_SEND_TO_COMPANY_WITHOUT_OFFER';
            $damageStatus = $this->em->getRepository(DamageStatus::class)->findOneBy(['key' => $key]);
            $damageRequest = new DamageRequest();
            $damageRequest->setCompany($user);
            $damageRequest->setDamage($damage);
            $damageRequest->setStatus($damageStatus);
            $damageRequest->setRequestedDate(new \DateTime('now'));
            $damageRequest->setCompanyEmail($email);
            $this->em->persist($damageRequest);
            $damage->setStatus($damageStatus);
            $damage->setCompanyAssignedBy($assignedBy);
            $this->em->flush();
        }
        return true;
    }
}
