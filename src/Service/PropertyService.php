<?php


namespace App\Service;


use App\Entity\Directory;
use App\Entity\Document;
use App\Entity\Folder;
use App\Entity\Payment;
use App\Entity\PropertyRoleInvitation;
use App\Entity\PropertyUser;
use App\Entity\PushNotification;
use App\Entity\User;
use App\Entity\UserIdentity;
use App\Entity\SubscriptionPlan;
use App\Entity\Property;
use App\Entity\Apartment;
use App\Utils\Constants;
use Doctrine\Persistence\ManagerRegistry;
use PhpParser\Node\Scalar\MagicConst\Dir;
use Symfony\Component\HttpFoundation\Request;
use App\Utils\ContainerUtility;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Utils\GeneralUtility;
use App\Entity\PropertyGroup;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Entity\Role;
use function Symfony\Component\HttpKernel\Log\format;

/**
 * Class PropertyService
 * @package App\Service
 */
class PropertyService extends BaseService
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
     * @var GeneralUtility $containerUtility
     */
    private GeneralUtility $generalUtility;

    /**
     * @var ParameterBagInterface $params
     */
    private ParameterBagInterface $params;

    /**
     * @var TranslatorInterface $translator
     */
    private TranslatorInterface $translator;

    /**
     * @var DMSService $dmsService
     */
    private DMSService $dmsService;

    /**
     * @var SecurityService $securityService
     */
    private SecurityService $securityService;

    private \Stripe\StripeClient $stripe;
    /**
     * @var UserService
     */
    private UserService $userService;

    /**
     * UserService constructor.
     * @param ManagerRegistry $doctrine
     * @param ContainerUtility $containerUtility
     * @param ParameterBagInterface $params
     * @param GeneralUtility $generalUtility
     * @param TranslatorInterface $translator
     * @param DMSService $dmsService
     * @param SecurityService $securityService
     * @param UserService $userService
     */
    public function __construct(ManagerRegistry $doctrine, ContainerUtility $containerUtility,
                                ParameterBagInterface $params, GeneralUtility $generalUtility,
                                TranslatorInterface $translator, DMSService $dmsService, SecurityService $securityService,
                                UserService $userService)
    {
        $this->doctrine = $doctrine;
        $this->params = $params;
        $this->translator = $translator;
        $this->containerUtility = $containerUtility;
        $this->generalUtility = $generalUtility;
        $this->dmsService = $dmsService;
        $this->securityService = $securityService;
        $this->userService = $userService;
        $this->stripe = new \Stripe\StripeClient($this->params->get('stripe_secret'));
    }

    /**
     * Get Plan start and end date based on subscription plan
     *
     * @param SubscriptionPlan $subscriptionPlan
     * @param \DateTime|null $userCreatedDate
     * @return array $date
     * @throws \Exception
     */
    public function getSubscriptionStartEndDates(SubscriptionPlan $subscriptionPlan, \DateTime $userCreatedDate = null): array
    {
        $period = $subscriptionPlan->getPeriod();
        $currentDate = new \DateTime();
        $createdDate = new \DateTime($userCreatedDate->format('Y-m-d'));
        $date['planStartDate'] = new \DateTime('today');
        $endDate = $currentDate->modify('+' . $period . ' days');
        if ($createdDate && $date['planStartDate'] < $endDate) {
            $date['planEndDate'] = $endDate;
        }
        return $date;
    }

    /**
     * Function to save documents against property
     *
     * @param Request $request
     * @param Property $property
     * @param UserIdentity $user
     * @param UserIdentity $owner
     * @param bool $propertyType
     * @param bool $isEdit
     * @param string $currentRole
     * @return Property
     * @throws \Exception
     */
    public function savePropertyInfo(Request $request, Property $property, UserIdentity $user, UserIdentity $owner,
                                     string $currentRole, bool $propertyType = true, bool $isEdit = false): Property
    {
        $em = $this->doctrine->getManager();
        $adminCreatingProperty = false;
        $subscriptionPlan = $em->getRepository(SubscriptionPlan::class)->findOneBy(['initialPlan' => 1, 'active' => 1]);
        $dateArray = $this->getSubscriptionStartEndDates($subscriptionPlan, $user->getCreatedAt());
        $janitor = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $request->request->get('janitor')]);
        $folderDisplayName = $this->getFolderName($owner, $request->request->get('address'), 'property');
        if (is_null($request->get('administrator')) && $currentRole == $this->camelCaseConverter(Constants::PROPERTY_ADMIN_ROLE)) {
            $adminCreatingProperty = true;
        }
        if (!is_null($request->get('administrator')) && in_array(Constants::PROPERTY_ADMIN_ROLE, $user->getUser()->getRoles())) {
            $adminCreatingProperty = true;
        }
        if (!$isEdit) {
            $requestKeys = ['createdBy' => $user, 'user' => $owner, 'subscriptionPlan' => $subscriptionPlan, 'planStartDate' => $dateArray['planStartDate'],
                'active' => true, 'recurring' => false, 'pendingPayment' => 0];
            if (($adminCreatingProperty === true || $request->get('administrator') === '') &&
                $currentRole === $this->camelCaseConverter(Constants::PROPERTY_ADMIN_ROLE)) {
                $requestKeys['administrator'] = $user;
            } elseif ($request->get('administrator') === '' && $currentRole === Constants::OWNER_ROLE) {
                $requestKeys['administrator'] = null;
            }
            $this->addOwnerRoleToUser($owner);
            $folder = $this->dmsService->createFolder($folderDisplayName['displayName'], $owner, true);
            $folder = reset($folder);
            if (!empty($folder)) {
                $folder = $em->getRepository(Folder::class)->findOneBy(['identifier' => $folder['identifier']]);
                $folder->setDisplayNameOffset($folderDisplayName['displayNameOffset']);
            }
        } else {
            $requestKeys = ['subscriptionPlan' => $subscriptionPlan, 'planStartDate' => $dateArray['planStartDate'],
                'active' => true, 'recurring' => false, 'pendingPayment' => 0];
            if (($adminCreatingProperty === true || $request->get('administrator') === '') &&
                $currentRole === $this->camelCaseConverter(Constants::PROPERTY_ADMIN_ROLE)) {
                $requestKeys['administrator'] = $user;
            } elseif ($request->get('administrator') === '' && $currentRole === Constants::OWNER_ROLE) {
                $requestKeys['administrator'] = null;
            }
            $folder = $property->getFolder();
            $folder->setDisplayName($folderDisplayName['displayName']);
            $requestKeys['updatedAt'] = new \DateTime('now');
            if (!empty($request->get('owner'))) {
                $propertyOwner = $this->findPropertyOwner($request, $currentRole, $user);
                $requestKeys['user'] = $propertyOwner;
                $this->addOwnerRoleToUser($propertyOwner);
            }
        }
        $requestKeys['folder'] = $folder;
        if (array_key_exists('planEndDate', $dateArray)) {
            $requestKeys += ['planEndDate' => $dateArray['planEndDate']];
        } else {
            $planEndDate = new \DateTime('now');
            $planEndDate->modify('+1 day');
            $requestKeys += ['planEndDate' => $planEndDate];
        }
        $property = $this->containerUtility->convertRequestKeysToSetters($requestKeys, $property);
        if (!$isEdit) {
            $this->saveTrialPlanToPayments($property, $user, $currentRole, $subscriptionPlan);
        }
        if ($adminCreatingProperty === false)
            $this->addAdministratorInvitation($janitor, $user, $property, false, $this->findPropertyAdmin($request, $currentRole, $user));
        if (!empty(trim($request->get('propertyGroup')))) {
            $this->savePropertyGroupDetails($property, $request->request->get('propertyGroup'));
        } else {
            $this->removeGroupFromProperty($property);
        }
        if (!empty($request->request->get('documents'))) {
            $this->dmsService->persistDocument($request->request->get('documents'), $property, 'documents');
        }
        if (!empty($request->request->get('coverImage'))) {
            $this->dmsService->deleteCoverImageIfExists($property);
            $this->dmsService->persistDocument($request->request->get('coverImage'), $property, 'coverImage');
        }
        if ($janitor instanceof UserIdentity) {
            $janitorRole = $em->getRepository(Role::class)->findOneBy(['roleKey' => $this->params->get('user_roles')['janitor']]);
            $janitor->addRole($janitorRole);
        }

        return $property;
    }

    /**
     * @param UserIdentity $propertyOwner
     */
    public function addOwnerRoleToUser(UserIdentity $propertyOwner): void
    {
        if (!in_array(Constants::OWNER_ROLE, $propertyOwner->getUser()->getRoles())) {
            $em = $this->doctrine->getManager();
            $ownerRole = $em->getRepository(Role::class)->findOneBy(['roleKey' => $this->params->get('user_roles')['owner']]);
            $propertyOwner->addRole($ownerRole);
        }
    }

    /**
     * @param Property $property
     * @param UserIdentity $user
     * @param string $currentRole
     * @param SubscriptionPlan $subscriptionPlan
     * @throws \Exception
     */
    public function saveTrialPlanToPayments(Property $property, UserIdentity $user, string $currentRole, SubscriptionPlan $subscriptionPlan): void
    {
        $em = $this->doctrine->getManager();
        $role = $em->getRepository(Role::class)->findOneBy(['roleKey' => $currentRole]);
        $requestKeys = [
            'user' => $user,
            'createdAt' => new \DateTime('now'),
            'deleted' => false,
            'isSuccess' => true,
            'isCompany' => $currentRole == Constants::COMPANY_ROLE,
            'role' => $role,
            'property' => $property,
            'subscriptionPlan' => $subscriptionPlan,
            'startDate' => $property->getPlanStartDate(),
            'endDate' => $property->getPlanEndDate(),
        ];

        $this->containerUtility->convertRequestKeysToSetters($requestKeys, new Payment());
    }

    /**
     *
     * @param Property $property
     * @param Request $request
     * @param bool|null $encoding
     * @param string|null $locale
     * @param UserIdentity|null $currentUser
     * @return array
     * @throws \Exception
     */
    public function generatePropertyArray(Property $property, Request $request, ?bool $encoding = true, ?string $locale = 'en', ?UserIdentity $currentUser = null): array
    {
        $em = $this->doctrine->getManager();
        $propertyArray = array();
        $janitorDetails = null;
        if ($property instanceof Property) {
            $propertyArray['id'] = $property->getIdentifier();
            $propertyArray['publicId'] = $property->getPublicId();
            $propertyArray['address'] = $property->getAddress();
            $propertyArray['streetName'] = $property->getStreetName();
            $propertyArray['streetNumber'] = $property->getStreetNumber();
            $propertyArray['postalCode'] = $property->getPostalCode();
            $propertyArray['city'] = $property->getCity();
            $propertyArray['state'] = $property->getState();
            $propertyArray['country'] = $property->getCountry();
            $propertyArray['countryCode'] = $property->getCountryCode();
            $propertyArray['planStartDate'] = $property->getPlanStartDate();
            $propertyArray['planEndDate'] = $property->getPlanEndDate();
            $propertyArray['isPropertyActive'] = $property->getActive();
            $propertyArray['deleted'] = $property->getDeleted();
            $propertyArray['cancelledOrExpired'] = $this->checkPropertyCancelledOrExpired($property);
            $propertyArray['administrator'] = $property->getAdministrator() ? $property->getAdministrator()->getPublicId() : null;
            $propertyArray['janitor'] = $property->getJanitor() ? $property->getJanitor()->getPublicId() : null;
            if ($property->getAdministrator() instanceof UserIdentity && !is_null($property->getAdministrator()->getCompanyName())) {
                $managedBy = $property->getAdministrator()->getCompanyName();
            } elseif ($property->getAdministrator() instanceof UserIdentity && is_null($property->getAdministrator()->getCompanyName())) {
                $managedBy = $property->getAdministrator()->getFirstName() . ' ' . $property->getAdministrator()->getLastName();
            } elseif ($property->getUser() instanceof UserIdentity && !is_null($property->getUser()->getCompanyName())) {
                $managedBy = $property->getUser()->getCompanyName();
            } else {
                $managedBy = $property->getUser()->getFirstName() . ' ' . $property->getUser()->getLastName();
            }
            $propertyArray['managedBy'] = $managedBy;
            $subcripton = $property->getSubscriptionPlan();
            if ($subcripton instanceof SubscriptionPlan) {
                $propertyArray['subscriptionPlan'] = $this->getPlanDetails($subcripton);
            }
            $propertyArray['currency'] = $property->getCurrency() ?? $this->params->get('default_currency');
            if ($property->getJanitor() instanceof UserIdentity) {
                $janitorDetails = $property->getJanitor()->getFirstName() . ' ' . $property->getJanitor()->getLastName();
                $janitorRoles = $this->securityService->fetchUserRole($property->getJanitor(), $locale);
                if (!empty($janitorRoles) && isset($janitorRoles['name'])) {
                    $janitorRoleString = implode(", ", $janitorRoles['name']);
                    $janitorDetails = $janitorDetails . "($janitorRoleString)";
                }
            }
            $propertyArray['janitorName'] = $janitorDetails;

            if (!empty($property->getPropertyGroups())) {
                foreach ($property->getPropertyGroups() as $group) {
                    $propertyArray['group']['groupName'] = $group->getName();
                    $propertyArray['group']['groupPublicId'] = $group->getPublicId();
                }
            } else {
                $propertyArray['group']['groupName'] = $this->translator->trans('ungrouped', [], null, $locale);
            }
            $propertyArray['user'] = $property->getUser()->getFirstName() . ' ' . $property->getUser()->getLastName();
            $directory = $em->getRepository(Directory::class)->findOneBy(['user' => $property->getUser(), 'invitor' => $currentUser, 'deleted' => false]);
            $propertyArray['owner'] = $directory instanceof Directory ? $directory->getPublicId() : '';
            $propertyArray['latitude'] = $property->getLatitude();
            $propertyArray['longitude'] = $property->getLongitude();
            $propertyArray['folder'] = $property->getFolder()->getPublicId();
            $documents = $em->getRepository(Document::class)->findBy(['property' => $property->getIdentifier(), 'deleted' => false, 'type' => 'property']);
            if (!empty($documents)) {
                foreach ($documents as $key => $document) {
                    $propertyArray['documents'][$key] = $this->dmsService->getUploadInfo($document, $request->getSchemeAndHttpHost(), $encoding);
                }
            }
            $images = $em->getRepository(Document::class)->findBy(['property' => $property->getIdentifier(), 'deleted' => false, 'type' => 'coverImage']);
            if (!empty($images)) {
                foreach ($images as $key => $image) {
                    $propertyArray['coverImage'][$key] = $this->dmsService->getUploadInfo($image, $request->getSchemeAndHttpHost(), $encoding);
                }
            }
            $propertyArray['isActive'] = $property->getActive();
            $propertyArray['activeObjectCount'] = $em->getRepository(Apartment::class)->getActiveApartmentCount($property->getIdentifier());
            $propertyArray['totalObjectCount'] = (null !== $property->getSubscriptionPlan()) ? $property->getSubscriptionPlan()->getApartmentMax() : null;
            $propertyArray['activeTenantCount'] = $em->getRepository(PropertyUser::class)->activeTenantCount($property->getIdentifier(), 'property');
            $propertyArray['isCreatedByAdmin'] = false;
            if ($request->headers->get('currentRole') === $this->camelCaseConverter(Constants::PROPERTY_ADMIN_ROLE) &&
                $property->getAdministrator() instanceof UserIdentity && $property->getAdministrator() === $currentUser &&
                $property->getCreatedBy() === $currentUser) {
                $propertyArray['isCreatedByAdmin'] = true;
            }
        }
        return $propertyArray;
    }

    /**
     * generatePropertyUserDetails
     *
     * @param Property $property
     * @param Request $request
     * @param string $locale
     * @return array
     */
    public function generatePropertyUserDetails(Property $property, Request $request, string $locale = 'en'): array
    {
        $em = $this->doctrine->getManager();
        $propertyArray = [];
        if ($property instanceof Property) {
            $propertyArray['publicId'] = $property->getPublicId();
            $propertyArray['name'] = $property->getAddress();
            $propertyArray['streetName'] = $property->getStreetName();
            $propertyArray['streetNumber'] = $property->getStreetNumber();
            $propertyArray['postalCode'] = $property->getPostalCode();
            $propertyArray['city'] = $property->getCity();
            $propertyArray['state'] = $property->getState();
            $propertyArray['country'] = $property->getCountry();
            $propertyArray['countryCode'] = $property->getCountryCode();
            $role = $em->getRepository(Role::class)->findOneBy(['roleKey' => $request->get('role')]);
            $propertyArray['invitedRole'] = $locale === 'en' ? $role->getName() : $role->getNameDe();
            $propertyRoleInvitation = $em->getRepository(PropertyRoleInvitation::class)->findOneBy(
                ['property' => $property, 'deleted' => false, 'role' => $role], ['createdAt' => 'DESC']
            );
            if ($propertyRoleInvitation instanceof PropertyRoleInvitation) {
                $propertyArray['expired'] = is_null($propertyRoleInvitation->getUpdatedAt()) ? false : true;
                $propertyArray['invitedBy'] = $propertyRoleInvitation->getInvitor()->getFirstName() . ' ' .
                    $property->getUser()->getLastName();
                $propertyArray['invitedAt'] = $propertyRoleInvitation->getCreatedAt();
            }
        }
        return $propertyArray;
    }

    /**
     * Get Subscription plan for a property
     *
     * @param Property $property
     * @param int|null $period
     *
     * @return SubscriptionPlan|null
     */
    public function getSubscriptionPlan(Property $property, int $period = null): ?SubscriptionPlan
    {
        $em = $this->doctrine->getManager();
        $apartmentCount = count($em->getRepository(Apartment::class)->findBy(['property' => $property, 'deleted' => false]));
        $defaultSubscriptionPeriod = $this->params->get('default_subscription_period');
        $subscriptionPeriod = (!is_null($period) && in_array($period, $defaultSubscriptionPeriod)) ? $period : $defaultSubscriptionPeriod[0];
        $plan = $em->getRepository(SubscriptionPlan::class)->getSubscriptionPlan($apartmentCount, $subscriptionPeriod);
        if ($plan instanceof SubscriptionPlan) {
            return $plan;
        }
        return $em->getRepository(SubscriptionPlan::class)->getBasicSubscriptionPlan($subscriptionPeriod);
    }

    /**
     * Get plans with same apartment count
     *
     * @param SubscriptionPlan $bpSubscriptionPlan
     * @param bool $inApp
     * @return array
     */
    public function getPlanArray(SubscriptionPlan $bpSubscriptionPlan, $inApp = false): array
    {
        $em = $this->doctrine->getManager();
        $result = array();
        $planArray = $em->getRepository(SubscriptionPlan::class)->getPlanArray($bpSubscriptionPlan);
        foreach ($planArray as $planObj) {
            $result[$planObj->getPeriod()] = $inApp == false ? $planObj->getAmount() : $planObj->getInappAmount();
        }

        return $result;
    }

    /**
     * Saves property and group details
     *
     * @param Property $property
     * @param string $propertyGroup
     * @return void
     */
    private function savePropertyGroupDetails(Property $property, string $propertyGroup): void
    {
        $em = $this->doctrine->getManager();
        if ($group = $em->getRepository(PropertyGroup::class)->findOneBy(['publicId' => $propertyGroup, 'deleted' => false])) {
            if ($property instanceof Property) {
                //remove if already have group then add
                if (!empty($property->getPropertyGroups())) {
                    $this->removeGroupFromProperty($property);
                }
                $property->addPropertyGroup($group);
                $em->persist($property);
                $em->flush();
            }
        } else {
            throw new ResourceNotFoundException('groupNotFound');
        }
    }

    /**
     * @param Property $property
     */
    private function removeGroupFromProperty(Property $property): void
    {
        $em = $this->doctrine->getManager();
        $existingGroups = $property->getPropertyGroups();
        foreach ($existingGroups as $existingGroup) {
            $property->removePropertyGroup($existingGroup);
            $em->persist($property);
        }
    }

    /**
     * @param Request $request
     * @param string $currentRole
     * @param UserIdentity $user
     * @return UserIdentity|null
     */
    public function findPropertyOwner(Request $request, string $currentRole, UserIdentity $user): ?UserIdentity
    {
        if ($currentRole === $this->params->get('user_roles')['property_admin'] && ($request->request->has('owner') &&
                !is_null($request->request->get('owner')))) {
            $em = $this->doctrine->getManager();
            $ownerDirectoryObj = $em->getRepository(Directory::class)->findOneBy(['publicId' => $request->request->get('owner')]);
            if ($ownerDirectoryObj instanceof Directory) {
                return $ownerDirectoryObj->getUser();
            }
        }
        return $user;
    }

    /**
     * @param UserIdentity $userIdentity
     * @param bool $propertyType
     * @param string|null $name
     * @return string
     */
    public function folderName(UserIdentity $userIdentity, bool $propertyType, ?string $name = null): string
    {
        $ownerExistingFolders = $this->doctrine->getRepository(Property::class)->getFolders(['user' => $userIdentity]);
        if (!is_null($name)) {
            $folderName = $name;
            $folders = array_column($ownerExistingFolders, 'displayNameOffset');
            $flag = false;
            if (!empty($folders)) {
                foreach ($folders as $key => $value) {
                    if (strpos($value, $folderName) !== false) {
                        $folderResidual = explode(' ', $value);
                        if ($folderResidual[0] === $folderName) {
                            $folderRes = end($folderResidual);
                            if ((int)$folderRes !== 0) {
                                $append = $folderRes + 1;
                                $folderName = $folderName . ' ' . $append;
                                $folderDisplayName = $folderName . ' ( ' . $append . ' )';
                                $flag = true;
                            }
                        }
                    }
                }
                if (!$flag && in_array($folderName, $folders)) {
                    $folderName = $folderName . ' 1';
                    $folderDisplayName = $folderName . ' ( ' . $append . ' )';
                }
            }
        } else {
            $type = $propertyType ? 'property' : 'object';
            $folderName = $userIdentity->getFirstName() . '\'s ' . $this->translator->trans($type) . ' ' . $this->translator->trans('folder');
//            if ($type === 'property') {
//                $ownerExistingFolders = $this->doctrine->getRepository(Property::class)->getFolders(['user' => $userIdentity]);
//            } else {
//                $folderName = $userIdentity->getFirstName() . '\'s ' . $this->translator->trans($type) . ' ' . $this->translator->trans('folder');
//                $ownerExistingFolders = $this->doctrine->getRepository(Apartment::class)->getFolders(['user' => $userIdentity]);
//            }
            $folders = array_column($ownerExistingFolders, 'displayName');
            if (in_array($folderName, $folders)) {
                $append = (int)filter_var(reset($folders), FILTER_SANITIZE_NUMBER_INT);
                if (is_numeric($append)) {
                    $append += 1;
                    $folderName .= ' ' . $append;
                } else {
                    $folderName = $folderName . ' 1';
                }
            }
        }
        return $folderName;
    }

    /**
     * @param array $properties
     * @param UserIdentity $userIdentity
     * @return bool
     */
    public function userAllocationInProperties(array $properties, UserIdentity $userIdentity): bool
    {
        $em = $this->doctrine->getManager();
        $allocations = $em->getRepository(PropertyUser::class)->findAllocations($properties, $userIdentity);
        if (!empty($allocations)) {
            return false;
        }
        return true;
    }

    /**
     * The function will return admin object for given property OR owner object if there is no admin
     *
     * @param Property $property
     * @param bool|null $role
     * @return array
     */
    public function getPropertyAdminOrOwner(Property $property, ?bool $role = false)
    {
        $hasActivePropertyAdmin = $this->hasPropertyAdmin($property);
        if ($role) {
            return ($hasActivePropertyAdmin) ? $property->getAdministrator() : $property->getUser();
        }
        return ($hasActivePropertyAdmin) ? ['user' => $property->getAdministrator(), 'role' => 'property_admin'] : ['user' => $property->getUser(), 'role' => 'owner'];
    }

    /**
     * check given property have an administrator
     *
     * @param Property $property
     *
     * @return boolean
     */
    public function hasPropertyAdmin(Property $property): bool
    {
        $propertyAdmin = $property->getAdministrator();
        if ($propertyAdmin instanceof UserIdentity) {
            return true;
        }

        return false;
    }

    /**
     *
     * @param Property $property
     * @param UserIdentity $user
     * @param array $params
     * @param string $locale
     * @param Request $request
     * @param int|null $count
     * @param int|null $page
     * @return array
     * @throws \Exception
     */
    public function getFilteredList(Property $property, UserIdentity $user, array $params, string $locale, Request $request, ?int $count, ?int $page): array
    {
        $em = $this->doctrine->getManager();
        $objects = $em->getRepository(Apartment::class)->filterProperties($property, $count, $page, $params);
        return array_map(function ($activeObjects) use ($em, $request) {
            $apartment = $em->getRepository(Apartment::class)->findOneBy(['publicId' => $activeObjects['publicId']->toRfc4122()]);
            $activeObjects['userCount'] = $em->getRepository(PropertyUser::class)->getActiveUserCount($apartment);
            $activeObjects['publicId'] = $activeObjects['publicId']->toRfc4122();
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
     * @param Request $request
     * @param string $currentRole
     * @param UserIdentity $user
     * @return UserIdentity|null
     */
    public function findPropertyAdmin(Request $request, string $currentRole, UserIdentity $user): ?UserIdentity
    {
        $em = $this->doctrine->getManager();
        $administrator = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $request->request->get('administrator')]);
        if ($currentRole === $this->camelCaseConverter(Constants::PROPERTY_ADMIN_ROLE) && !$administrator instanceof UserIdentity) {
            $administrator = $user;
        }

        return $administrator;
    }

    /**
     *
     * @param UserIdentity $userIdentity
     * @param string|null $name
     * @param string|null $type
     * @return array
     */
    public function getFolderName(UserIdentity $userIdentity, ?string $name = null, ?string $type = ''): array
    {
        $entity = ucfirst($type);
        $ownerExistingFolders = $this->doctrine->getRepository("App\\Entity\\$entity")->getFolders(['user' => $userIdentity]);
        $propertyNameArray = [];
        if (!is_null($name)) {
            $folderName = $name;
            $folders = array_column($ownerExistingFolders, 'displayNameOffset');
            $propertyNameArray = ['displayName' => $folderName, 'displayNameOffset' => $folderName];
//            TO DO: naming need to fix
            return $propertyNameArray;
            $flag = false;
            if (!empty($folders)) {
                foreach ($folders as $key => $value) {
                    if (strpos($value, $folderName) !== false) {
                        $folderResidual = explode(' ', $value);
                        $folderRes = end($folderResidual);
                        if ((int)$folderRes !== 0) {
                            if ($name === $value) {
                                $flag = false;
                                $propertyNameArray = ['displayName' => ucfirst($name), 'displayNameOffset' => $name];
                            } else {
                                $append = (int)$folderRes + 1;
                                $folderDisplayName = $folderName . ' ( ' . $append . ' )';
                                $folderName = $folderName . ' ' . $append;
                                $flag = true;
                                $propertyNameArray = ['displayName' => ucfirst($folderDisplayName), 'displayNameOffset' => $folderName];
                            }
                        }
                    }
                }
                if (!$flag && in_array($folderName, $folders)) {
                    $folderDisplayName = $folderName . ' ( 1 )';
                    $folderName = $folderName . ' 1';
                    $propertyNameArray = ['displayName' => ucfirst($folderDisplayName), 'displayNameOffset' => $folderName];
                }

            }
        }

        return $propertyNameArray;
    }

    /**
     * updatePropertyExpiry
     *
     * @param Property $property
     * @param SubscriptionPlan $subscriptionPlan
     * @param \DateTime|null $planEndDate
     * @param bool $recurring
     * @return boolean
     * @throws \Exception
     */
    public function updatePropertyExpiry(Property $property, SubscriptionPlan $subscriptionPlan, \DateTime $planEndDate = null, bool $recurring = false): bool
    {
        $curDate = new \DateTime();
        $currentDate = new \DateTime($curDate->format('Y-m-d'));
        if (is_null($planEndDate)) {
            $planEndDate = clone $property->getPlanEndDate();
            $expiringDays = $currentDate->diff($planEndDate)->format('%r%a');
            if ($expiringDays > 0) {
                $planEndDate = $planEndDate->modify('+' . $subscriptionPlan->getPeriod() . ' day');
            } else {
                $planEndDate = $currentDate->modify('+' . $subscriptionPlan->getPeriod() . ' day');
            }
            $property->setPlanEndDate($planEndDate);
        } else {
            $property->setPlanEndDate($planEndDate);
        }
        $property->setSubscriptionPlan($subscriptionPlan);
        $property->setRecurring($recurring);
        $property->setActive(true);

        return true;
    }

    /**
     * @param Property $property
     * @param Request $request
     * @param bool $isSubscriptionList
     * @param string|null $locale
     * @return array
     * @throws \Exception
     */
    public function generateSubscriptionArray(Property $property, Request $request, bool $isSubscriptionList = false, ?string $locale): array
    {
        $em = $this->doctrine->getManager();
        $propertyArray = array();
        if ($property instanceof Property) {
            $propertyArray['publicId'] = $property->getPublicId();
            $propertyArray['id'] = $property->getIdentifier();
            $propertyArray['address'] = $property->getAddress();
            $propertyArray['streetName'] = $property->getStreetName();
            $propertyArray['active'] = $property->getActive();
            $propertyArray['streetNumber'] = $property->getStreetNumber();
            $propertyArray['active'] = $property->getActive();
            $propertyArray['postalCode'] = $property->getPostalCode();
            $propertyArray['planEndDate'] = $property->getPlanEndDate();
            $propertyArray['planStartDate'] = $property->getPlanStartDate();
            $propertyArray['expiredDate'] = $property->getExpiredDate();
            $propertyArray['activeObjectCount'] = $em->getRepository(Apartment::class)->getActiveApartmentCount($property->getIdentifier());
            $propertyArray['totalObjectCount'] = (null !== $property->getSubscriptionPlan()) ? $property->getSubscriptionPlan()->getApartmentMax() : null;
//            $propertyArray['subscriptionPlan'] = $this->getSubscriptionPlan($property, $property->getSubscriptionPlan()->getPeriod());
//            $inAppAmount = (bool)$request->get('inApp', 0);
//            if ($propertyArray['subscriptionPlan'] instanceof SubscriptionPlan) {
//                $propertyArray['planArray'] = $this->getPlanArray($propertyArray['subscriptionPlan'], $inAppAmount);
//            }
            if ($property->getSubscriptionPlan() instanceof SubscriptionPlan) {
                $propertyArray['subscriptionPlan'] = $this->getPlanDetails($property->getSubscriptionPlan(), $locale);
                $propertyArray['totalObjectCount'] = (null !== $property->getSubscriptionPlan()) ? $property->getSubscriptionPlan()->getApartmentMax() : null;
            }
            if ($isSubscriptionList) {
                $propertyArray['isSubscriptionCancelled'] = $property->getIsCancelledSubscription();
                if ($property->getIsCancelledSubscription()) {
                    $propertyArray['cancelledDate'] = $property->getCancelledDate();
                }
            }
            $images = $em->getRepository(Document::class)->findBy(['property' => $property->getIdentifier(), 'deleted' => false, 'type' => 'coverImage']);
            if (!empty($images)) {
                foreach ($images as $key => $image) {
                    $propertyArray['coverImage'][$key] = $this->dmsService->getUploadInfo($image, $request->getSchemeAndHttpHost(), false);
                }
            }
            $propertyArray['currency'] = $property->getCurrency() ?? $this->params->get('default_currency');
        }

        return $propertyArray;
    }

    /**
     *
     * @param Request $request
     * @return bool
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Exception
     */
    public function cancelSubscription(Request $request): bool
    {
        $em = $this->doctrine->getManager();
        $property = $em->getRepository(Property::class)->findOneBy(['publicId' => $request->get('propertyId')]);
        if (is_null($property) || is_null($property->getStripeSubscription())) {
            throw new \Exception('unsubscribeFailed');
        }
        $subscription = $this->stripe->subscriptions->retrieve($property->getStripeSubscription());
        $subscription->cancel();
        $property->setStripeSubscription(null);
        $property->setRecurring(false);
        $property->setIsCancelledSubscription(true);
        $currentDate = new \DateTime('now');
        $property->setCancelledDate($currentDate);
        $em->flush();

        return true;
    }

    /**
     * setPropertyArray
     *
     * @param Property $property
     * @param Request $request
     * @param string $locale
     * @return array
     * @throws \Exception
     */
    public function comparePlans(Property $property, Request $request, string $locale): array
    {
        $em = $this->doctrine->getManager();
        $data = [];
        $plans = $em->getRepository(SubscriptionPlan::class)->findBy(['deleted' => false, 'active' => 1], ['amount' => 'ASC']);
        foreach ($plans as $key => $plan) {
            if (true == $plan->getInitialPlan() && $plan != $property->getSubscriptionPlan()) {
                continue;
            }
            if ($plan === $property->getSubscriptionPlan()) {
                $data['plans']['currentPlan'] = $this->getPlanDetails($plan, $locale);
            } else {
                $data['plans']['otherPlans'][] = $this->getPlanDetails($plan, $locale);
            }
        }

        $data['property'] = $this->setPropertyArray(['identifier' => $property->getIdentifier()], $request);

        return array_merge($data);
    }

    /**
     *
     * @param array|null $details
     * @param SubscriptionPlan $plan
     * @param string $locale
     * @return array
     */
    private function translateDetails(?array $details, SubscriptionPlan $plan, string $locale): array
    {
        $data = [];
        $apartmentMax = $plan->getApartmentMax();
        if (!empty($details)) {
            foreach ($details as $key => $detail) {
                if (is_array($detail)) {
                    $data[$key] = $this->translateDetails($detail, $plan, $locale);
                } else {
                    $keyRenamed = false;
                    $detailKey = $detail;
                    if (str_contains($detail, 'description_')) {
                        $formatKey = explode('_', $detail);
                        $detailKey = reset($formatKey);
                        $keyRenamed = true;
                    }
                    if ($keyRenamed == true) {
                        $data[$detailKey] = $this->translator->trans($detail, ['%objectNumber%' => $apartmentMax], null, $locale);
                    } else {
                        $data[$detail] = $this->translator->trans($detail, ['%objectNumber%' => $apartmentMax], null, $locale);

                    }
                }
            }
        }

        return $data;
    }

    /**
     *
     * @param array $property
     * @param Request $request
     * @param string|null $locale
     * @return array
     * @throws \Exception
     */
    public function setPropertyArray(array $property, Request $request, ?string $locale = 'en'): array
    {
        $em = $this->doctrine->getManager();
        $property = $em->getRepository(Property::class)->find($property['identifier']);
        return $this->generateSubscriptionArray($property, $request, true, $locale);
    }

    /**
     *
     * @param SubscriptionPlan $plan
     * @param string $locale
     * @return array
     */
    public function getPlanDetails(SubscriptionPlan $plan, string $locale = 'en'): array
    {

        $data['publicId'] = $plan->getPublicId();
        $data['id'] = $plan->getIdentifier();
        $data['apartmentMin'] = $plan->getApartmentMin();
        $data['apartmentMax'] = $plan->getApartmentMax();
        $data['name'] = ($locale == 'de') ? $plan->getNameDe() : $plan->getName();
        $data['amount'] = number_format($plan->getAmount(), 2, '.', '');
        $data['period'] = ($plan->getPeriod() == 30) ?
            $this->translator->trans('monthlyPayment', [], null, $locale) : null;
        $data['isFreePlan'] = $plan->getInitialPlan();
        $data['details'] = $this->translateDetails($plan->getDetails(), $plan, $locale);
        $data['currency'] = $this->params->get('default_currency');
        $data['colorCode'] = $plan->getColorCode();
        $data['textColor'] = $plan->getTextColor();

        return $data;
    }

    /**
     *
     * @param SubscriptionPlan $plan
     * @param Property $property
     * @param string $locale
     * @return array
     */
    public function getPlanData(SubscriptionPlan $plan, Property $property, string $locale): array
    {
        if ($property->getSubscriptionPlan() instanceof SubscriptionPlan) {
            $data['currentPlan'] = $this->getPlanDetails($property->getSubscriptionPlan(), $locale);
        }
        $data['newPlan'] = $this->getPlanDetails($plan, $locale);
        return $data;
    }

    /**
     * addAdministratorInvitation
     *
     * @param UserIdentity|null $janitor
     * @param UserIdentity $loggedUser
     * @param Property $property
     * @param bool $janitorInvite
     * @param UserIdentity|null $propertyAdministrator
     * @param bool $sendInvite
     * @throws \Exception
     */
    public function addAdministratorInvitation(?UserIdentity $janitor, UserIdentity $loggedUser, Property $property,
                                               bool $janitorInvite = false, ?UserIdentity $propertyAdministrator = null, bool $sendInvite = false): void
    {
        $em = $this->doctrine->getManager();
        $invitor = $loggedUser;
        $parameter['property'] = $property;
        if ($janitor instanceof UserIdentity && ($property->getJanitor() instanceof UserIdentity && $property->getJanitor() !== $janitor)
            || !$property->getJanitor() instanceof UserIdentity) {
            $role = $em->getRepository(Role::class)->findOneBy(['roleKey' => Constants::JANITOR_ROLE]);
            $invitation = $em->getRepository(PropertyRoleInvitation::class)->findOneBy([
                'invitee' => $janitor,
                'role' => $role,
                'property' => $property,
                'deleted' => false
            ]);
            if (!$invitation instanceof PropertyRoleInvitation && $janitorInvite) {
                $em->getRepository(PropertyRoleInvitation::class)
                    ->createPropertyRoleInvitation($janitor, $invitor, $role, $property);
            }
            if ($invitation instanceof PropertyRoleInvitation && !$janitorInvite) {
                $invitation->setDeleted(true);
            }
            $propertyUsers = $em->getRepository(PropertyUser::class)->findBy([
                'user' => $janitor, 'property' => $property, 'isActive' => true, 'deleted' => false
            ]);

            if (!empty($propertyUsers)) {
                foreach ($propertyUsers as $propertyUser) {
                    $propertyUser->setIsJanitor($janitorInvite);
                }
            } else {
                $propertyUser = new PropertyUser();
                $propertyUser->setProperty($property);
                $propertyUser->setUser($janitor);
                $propertyUser->setRole($janitorInvite ? $role : null);
                $propertyUser->setIsActive(true);
                $propertyUser->setIsJanitor($janitorInvite);
                $em->persist($propertyUser);
            }
            $em->flush();
            if ($janitorInvite) {
                $janitor->addRole($role);
                $em->flush();
                if ($sendInvite) {
                    $janitorLanguage = $janitor->getLanguage() ?? $this->params->get('default_language');
                    $parameter['url'] = $this->params->get('FE_DOMAIN') . $this->params->get('property_administrative_invitation_url')
                        . DIRECTORY_SEPARATOR . $property->getPublicId() . DIRECTORY_SEPARATOR . Constants::JANITOR_ROLE .
                        DIRECTORY_SEPARATOR . $janitorLanguage;
                    $subject = Constants::PROPERTY_ADMINISTRATIVE_INVITATION_JANITOR_SUBJECT;
                    $parameter['invite'] = 'janitor';
                    $parameter['invitedBy'] = $this->translator->trans('invitedBy', [], null, $janitorLanguage)
                        . ' : ' . $loggedUser->getFirstName() . ' ' . $loggedUser->getLastName();
                    $this->containerUtility->sendEmail($janitor, 'PropertyAdministrativeInvitation',
                        $janitorLanguage, $subject, $parameter);
                    $this->sendPushNotification($janitor, $property, $subject, $role, Constants::ADMINISTRATIVE_INVITE_EVENT);
                } else {
                    $property->setJanitor($janitor);
                    $em->flush();
                }
            }
        } else {
            if (!$janitorInvite) {
                $property->setJanitor(null);
                $janitorInvitationStatus = $em->getRepository(PropertyRoleInvitation::class)
                    ->checkJanitorInvitationStatus($property->getIdentifier(), Constants::JANITOR_ROLE, $janitor);
                if ($janitorInvitationStatus instanceof PropertyRoleInvitation) {
                    $janitorInvitationStatus->setDeleted(true);
                }
                $propertyUsers = $em->getRepository(PropertyUser::class)->findBy([
                    'user' => $janitor, 'property' => $property, 'isActive' => true, 'deleted' => false
                ]);
                if (!empty($propertyUsers)) {
                    foreach ($propertyUsers as $propertyUser) {
                        $propertyUser->setIsJanitor($janitorInvite);
                    }
                }
                $em->flush();
            } else {
                $role = $em->getRepository(Role::class)->findOneBy(['roleKey' => Constants::JANITOR_ROLE]);
                $janitor->addRole($role);
                if ($sendInvite) {
                    $janitorLanguage = $janitor->getLanguage() ?? $this->params->get('default_language');
                    $parameter['url'] = $this->params->get('FE_DOMAIN') . $this->params->get('property_administrative_invitation_url')
                        . DIRECTORY_SEPARATOR . $property->getPublicId() . DIRECTORY_SEPARATOR . Constants::JANITOR_ROLE .
                        DIRECTORY_SEPARATOR . $janitorLanguage;
                    $subject = Constants::PROPERTY_ADMINISTRATIVE_INVITATION_JANITOR_SUBJECT;
                    $parameter['invite'] = 'janitor';
                    $parameter['invitedBy'] = $this->translator->trans('invitedBy', [], null, $janitorLanguage)
                        . ' : ' . $loggedUser->getFirstName() . ' ' . $loggedUser->getLastName();
                    $this->containerUtility->sendEmail($janitor, 'PropertyAdministrativeInvitation',
                        $janitorLanguage, $subject, $parameter);
                    $this->sendPushNotification($janitor, $property, $subject, $role, Constants::ADMINISTRATIVE_INVITE_EVENT);
                }
            }
            $propertyUser = $em->getRepository(PropertyUser::class)->findOneBy([
                'user' => $janitor, 'property' => $property, 'isActive' => true, 'deleted' => false
            ]);
            if ($propertyUser instanceof PropertyUser) {
                $propertyUser->setIsJanitor($janitorInvite);
            }
            $em->flush();
        }
        if (!is_null($propertyAdministrator) && ((!is_null($property->getAdministrator()) && $property->getAdministrator() != $propertyAdministrator)
                || (is_null($property->getAdministrator()) && $property->getAdministrator() != $propertyAdministrator))) {
            $role = $em->getRepository(Role::class)->
            findOneBy(['roleKey' => $this->camelCaseConverter(Constants::PROPERTY_ADMIN_ROLE)]);
            $em->getRepository(PropertyRoleInvitation::class)
                ->createPropertyRoleInvitation($propertyAdministrator, $invitor, $role, $property);
            $propAdminLanguage = $propertyAdministrator->getLanguage() ?? $this->params->get('default_language');
            $parameter['url'] = $this->params->get('FE_DOMAIN') . $this->params->get('property_administrative_invitation_url')
                . DIRECTORY_SEPARATOR . $property->getPublicId() . DIRECTORY_SEPARATOR .
                $this->camelCaseConverter(Constants::PROPERTY_ADMIN_ROLE) . DIRECTORY_SEPARATOR . $propAdminLanguage;
            $subject = 'PROPERTY_ADMINISTRATIVE_INVITATION_ADMIN_SUBJECT';
            $parameter['invite'] = 'prop';
            $parameter['invitedBy'] = $this->translator->trans('invitedBy', [], null, $propAdminLanguage)
                . ' : ' . $loggedUser->getFirstName() . ' ' . $loggedUser->getLastName();
            $this->containerUtility->sendEmail($propertyAdministrator, 'PropertyAdministrativeInvitation',
                $propAdminLanguage, $subject, $parameter);
            $this->sendPushNotification($propertyAdministrator, $property, $subject, $role, Constants::ADMINISTRATIVE_INVITE_EVENT);
        }
    }

    /**
     * @param array $request
     * @param PropertyRoleInvitation $propertyRoleInvitation
     * @param Property $property
     * @return bool
     * @throws \Exception
     */
    public function savePropertyRoleInvitation(array $request, PropertyRoleInvitation $propertyRoleInvitation, Property $property): bool
    {
        $em = $this->doctrine->getManager();
        $invitorRole = ($propertyRoleInvitation->getInvitor()->getIdentifier() == $propertyRoleInvitation->getProperty()->getUser()->getIdentifier()) ?
            Constants::OWNER_ROLE : Constants::PROPERTY_ADMIN_ROLE;
        $role = $em->getRepository(Role::class)->findOneBy(['roleKey' => $this->camelCaseConverter($invitorRole)]);
        if ($request['accepted'] == true) {
            $propertyRoleInvitation->setInvitationAcceptedDate(new \DateTime('now'));
            if ($propertyRoleInvitation->getRole()->getRoleKey() == Constants::JANITOR_ROLE) {
                $property->setJanitor($propertyRoleInvitation->getInvitee());
                $parameter['invite'] = 'janitor';
                $parameter['name'] = $propertyRoleInvitation->getInvitee()->getFirstName() . ' ' . $propertyRoleInvitation->getInvitee()->getLastName();
                $subject = Constants::PROPERTY_JANITOR_INVITATION_ACCEPT_SUBJECT;
            } else {
                $property->setAdministrator($propertyRoleInvitation->getInvitee());
                $parameter['invite'] = 'prop';
                $parameter['name'] = $propertyRoleInvitation->getInvitee()->getFirstName() . ' ' . $propertyRoleInvitation->getInvitee()->getLastName();
                $subject = Constants::PROPERTY_ADMINISTRATIVE_INVITATION_ACCEPT_SUBJECT;
            }
            $inviteeExistingRoles = $propertyRoleInvitation->getInvitee()->getRole();
            $existingRoleKeys = [];
            if (!empty($inviteeExistingRoles)) {
                foreach ($inviteeExistingRoles as $role) {
                    $existingRoleKeys[] = $role->getRoleKey();
                }
            }
            $role = $propertyRoleInvitation->getRole();
            $camelCasePropertyAdminRole = $this->camelCaseConverter(Constants::PROPERTY_ADMIN_ROLE);
            if (!in_array(Constants::JANITOR_ROLE, $existingRoleKeys) ||
                !in_array($camelCasePropertyAdminRole, $existingRoleKeys)) {
                $propertyRoleInvitation->getInvitee()->addRole($role);
            }
            $em->getRepository(PropertyRoleInvitation::class)->removeOldInvitations($propertyRoleInvitation);
            $this->containerUtility->sendEmail(
                $propertyRoleInvitation->getInvitor(),
                'PropertyAdministrativeInvitationAccept',
                $propertyRoleInvitation->getInvitor()->getLanguage() ?? $this->params->get('default_language'),
                $subject,
                $parameter
            );
            $this->sendPushNotification(
                $propertyRoleInvitation->getInvitor(),
                $property,
                $subject,
                $role,
                Constants::ADMINISTRATIVE_INVITE_ACCEPTED
            );
        } else {
            $propertyRoleInvitation->setInvitationRejectedDate(new \DateTime('now'));
            if ($propertyRoleInvitation->getRole()->getRoleKey() == Constants::JANITOR_ROLE) {
                $parameter['reject'] = 'janitor';
                $subject = Constants::PROPERTY_JANITOR_INVITATION_REJECT_SUBJECT;
                $parameter['name'] = $propertyRoleInvitation->getInvitee()->getFirstName() . ' ' . $propertyRoleInvitation->getInvitee()->getLastName();
            } else {
                $parameter['reject'] = 'prop';
                $subject = Constants::PROPERTY_ADMINISTRATIVE_INVITATION_REJECT_SUBJECT;
                $parameter['name'] = $propertyRoleInvitation->getInvitee()->getFirstName() . ' ' . $propertyRoleInvitation->getInvitee()->getLastName();
            }
            $this->containerUtility->sendEmail(
                $propertyRoleInvitation->getInvitor(),
                'PropertyAdministrativeInvitationReject',
                $propertyRoleInvitation->getInvitor()->getLanguage() ?? $this->params->get('default_language'),
                $subject,
                $parameter
            );
            $this->sendPushNotification(
                $propertyRoleInvitation->getInvitor(),
                $property,
                $subject,
                $role,
                Constants::ADMINISTRATIVE_INVITE_REJECTED
            );
            $propertyRoleInvitation->setDeleted(true);
        }
        (isset($request['reason']) && !is_null($request['reason'])) ? $propertyRoleInvitation->setReason($request['reason']) : '';
        $propertyRoleInvitation->setUpdatedAt(new \DateTime('now'));
        $em->flush();

        return true;
    }

    /**
     * @param UserIdentity $userObj
     * @param Property $property
     * @param string $subject
     * @param Role $userRole
     * @param string $event
     * @throws \Exception
     */
    public function sendPushNotification(UserIdentity $userObj, Property $property, string $subject, Role $userRole, string $event): void
    {

        $deviceIds = $this->userService->getDeviceIds($userObj);
        $params = array(
            'property' => $property,
            'toUser' => $userObj,
            'message' => $this->translator->trans($subject, [], null, 'en') . ' : ' . $property->getAddress(),
            'messageDe' => $this->translator->trans($subject, [], null, 'de') . ' : ' . $property->getAddress(),
            'event' => $event,
            'role' => $userRole,
            'createdAt' => new \DateTime()
        );
        $notificationId = $this->savePropertyAdministrativeInvitationNotification($params);
        if (!empty($deviceIds)) {
            $notificationParams = array(
                "propertyId" => $property->getPublicId(),
                'userRole' => $userRole->getRoleKey(),
                "message" => $this->translator->trans($subject, [], null, $userObj->getLanguage() ?? 'en') . ' : ' . $property->getAddress(),
                'notificationId' => $notificationId,
                'event' => $event
            );
            if (in_array($event, Constants::ADMIN_NO_REDIRECTION_EVENTS)) {
                $notificationParams['redirection'] = false;
            }
            $this->containerUtility->sendPushNotification($notificationParams, $deviceIds);
        }
    }

    /**
     * savePushNotification
     *
     * @param array $params
     * @return string
     * @throws \Exception
     */
    public function savePropertyAdministrativeInvitationNotification(array $params): string
    {
        $notification = $this->containerUtility->convertRequestKeysToSetters($params, new PushNotification());

        return $notification->getPublicId();
    }

    /**
     *
     * @param Property $property
     * @return array
     */
    public function getApartments(Property $property): array
    {
        $data = [];
        $apartmentArray = $property->getApartments();
        foreach ($apartmentArray as $apartment) {
            $data[]['id'] = $apartment->getPublicId();
            $data[]['status'] = $apartment->getActive();
        }

        return $data;
    }

    /**
     * @param Property $property
     * @return string
     */
    public function checkPropertyCancelledOrExpired(Property $property): string
    {
        if (!is_null($property->getExpiredDate())) {
            return Constants::PROPERTY_SUBSCRIPTION_EXPIRED;
        }
        if ($property->getIsCancelledSubscription() == true) {
            return Constants::PROPERTY_SUBSCRIPTION_CANCELLED;
        }
        if (($property->getActive() == false) || ($property->getDeleted() == true)) {
            return Constants::PROPERTY_INACTIVE;
        }

        return Constants::PROPERTY_ACTIVE;
    }

    /**
     * checkJanitorAvailableOrInvited
     *
     * @param Property $property
     * @param string|null $directory
     * @return string
     */
    public function checkJanitorAvailableOrInvited(Property $property, ?string $directory = null)
    {
        if (!is_null($directory)) {
            $directory = $this->doctrine->getRepository(Directory::class)->findOneBy(['publicId' => $directory]);
        }
        if ($property->getJanitor() instanceof UserIdentity) {
            if ($directory instanceof Directory && $directory->getUser() === $property->getJanitor()) {
                return false;
            }
            return Constants::JANITOR_PRESENT;
        }
        $janitorRole = $this->doctrine->getRepository(Role::class)->findOneBy(['roleKey' => Constants::JANITOR_ROLE]);
        $roleInvitation = $this->doctrine->getRepository(PropertyRoleInvitation::class)->findOneBy(
            [
                'property' => $property,
                'role' => $janitorRole,
                'deleted' => false
            ]
        );
        if ($roleInvitation instanceof PropertyRoleInvitation) {
            if ($directory instanceof Directory && $directory->getUser() === $property->getJanitor()) {
                return false;
            }
            return Constants::JANITOR_INVITED;
        }
        return false;
    }
}
