<?php


namespace App\Service;


use App\Entity\Address;
use App\Entity\DamageRequest;
use App\Entity\CompanySubscriptionPlan;
use App\Entity\DamageStatus;
use App\Entity\Property;
use App\Entity\PropertyUser;
use App\Entity\UserIdentity;
use App\Utils\Constants;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\DamageOffer;
use App\Entity\Damage;
use App\Entity\DamageOfferField;
use App\Utils\ContainerUtility;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Helpers\FileUploadHelper;
use App\Entity\DamageImage;
use App\Entity\CompanyRating;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\Role;
use Symfony\Component\Form\Form;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Permission;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Utils\ValidationUtility;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Class CompanyService
 * @package App\Service
 */
class CompanyService extends BaseService
{
    /**
     * @var ManagerRegistry $doctrine
     */
    private ManagerRegistry $doctrine;

    /**
     * @var UserService $userService
     */
    private UserService $userService;

    /**
     * @var ContainerUtility $containerUtility
     */
    private ContainerUtility $containerUtility;

    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $parameterBag;

    /**
     * @var FileUploadHelper
     */
    private FileUploadHelper $fileUploadHelper;

    /**
     * @var ValidationUtility $validationUtility
     */
    private ValidationUtility $validationUtility;

    /**
     * @var RegistrationService $registrationService
     */
    private RegistrationService $registrationService;

    /**
     * @var TranslatorInterface $translator
     */
    private TranslatorInterface $translator;

    /**
     * @var ParameterBagInterface $params
     */
    private ParameterBagInterface $params;

    /**
     * @var UserPasswordHasherInterface
     */
    private UserPasswordHasherInterface $passwordHasher;

    /**
     * CompanyService constructor.
     * @param ManagerRegistry $doctrine
     * @param UserService $userService
     * @param ContainerUtility $containerUtility
     * @param FileUploadHelper $fileUploadHelper
     * @param RegistrationService $registrationService
     * @param ParameterBagInterface $params
     * @param TranslatorInterface $translator
     * @param ValidationUtility $validationUtility
     * @param UserPasswordHasherInterface $passwordHasher
     */
    public function __construct(ManagerRegistry $doctrine,
                                UserService $userService,
                                ContainerUtility $containerUtility,
                                FileUploadHelper $fileUploadHelper,
                                RegistrationService $registrationService,
                                ParameterBagInterface $params,
                                TranslatorInterface $translator,
                                ValidationUtility $validationUtility,
                                UserPasswordHasherInterface $passwordHasher)
    {
        $this->doctrine = $doctrine;
        $this->userService = $userService;
        $this->containerUtility = $containerUtility;
        $this->parameterBag = $containerUtility->getParameterBag();
        $this->fileUploadHelper = $fileUploadHelper;
        $this->registrationService = $registrationService;
        $this->translator = $translator;
        $this->params = $params;
        $this->validationUtility = $validationUtility;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     *
     * @param UserIdentity $user
     * @param UserIdentity $currentUser
     * @return array
     */
    public function getCompanyDetail(UserIdentity $user, UserIdentity $currentUser): array
    {
        $em = $this->doctrine->getManager();
        $finalList = [];
        $lists = $em->getRepository(PropertyUser::class)->getCompanyDetail($user, $currentUser);
        $lists['invitedAt'] = $user->getInvitedAt();
        if ($user instanceof UserIdentity) {
            $finalList = $this->getCommonProperties($user);
            $finalList['janitorAllocations'] = $em->getRepository(Property::class)->getJanitorAllocations($user, $currentUser);
        }
        $finalList['objectAllocations'] = $lists;

        return $finalList;
    }

    /**
     *
     * @param UserIdentity $user
     * @param UserIdentity $currentUser
     * @return array
     */
    public function getAdministratorDetails(UserIdentity $user, UserIdentity $currentUser): array
    {
        $em = $this->doctrine->getManager();
        $finalList = [];
        $lists = $em->getRepository(Property::class)->getAdminDetails($user, $currentUser);
        if ($user instanceof UserIdentity) {
            $finalList = $this->getCommonProperties($user);
        }
        $finalList['allocations'] = $lists;

        return $finalList;
    }

    /**
     * @param UserIdentity $user
     * @return array
     */
    private function getCommonProperties(UserIdentity $user): array
    {
        $em = $this->doctrine->getManager();
        $finalList = array();
        $finalList['details']['firstName'] = $user->getFirstName();
        $finalList['details']['lastName'] = $user->getLastName();
        $finalList['details']['email'] = $user->getUser()->getProperty();
        $finalList['details']['isRegisteredUser'] = !is_null($user->getUser()->getFirstLogin());
        $finalList['details']['invitedOn'] = $user->getCreatedAt();
        $finalList['details']['dob'] = $user->getDob();
        $finalList['details']['language'] = $user->getLanguage();
        $finalList['details']['lastLogin'] = $user->getUser()->getLastLogin();
        $finalList['details']['firstLogin'] = $user->getUser()->getFirstLogin();
        $finalList['details']['isSystemGeneratedEmail'] = $user->getIsSystemGeneratedEmail();
        $address = $em->getRepository(Address::class)->findOneBy(['user' => $user, 'deleted' => 0]);
        if ($address instanceof Address) {
            $finalList['details']['phone'] = $address->getPhone();
            $finalList['details']['landLine'] = $address->getLandLine();
            $finalList['details']['street'] = $address->getStreet();
            $finalList['details']['streetNumber'] = $address->getStreetNumber();
            $finalList['details']['country'] = $address->getCountry();
            $finalList['details']['city'] = $address->getCity();
            $finalList['details']['countryCode'] = $address->getCountryCode();
            $finalList['details']['latitude'] = $address->getLatitude();
            $finalList['details']['longitude'] = $address->getLongitude();
            $finalList['details']['state'] = $address->getState();
            $finalList['details']['zipCode'] = $address->getZipCode();
        }
        return $finalList;
    }

    /**
     *
     * @param UserIdentity $company
     * @return array
     */
    public function getCompanyDetailArray(UserIdentity $company): array
    {
        $data = [];
        $data['publicId'] = $company->getPublicId();
        $data['name'] = $company->getFirstName() . ' ' . $company->getLastName();
        $data['address'] = $this->userService->getAddressDetails($company->getAddresses());
        $data['email'] = $company->getUser()->getProperty();

        return $data;
    }

    /**
     * processDamage
     *
     * function to process damage ticket
     *
     * @param Request $request
     * @param Damage $damage
     * @param DamageOffer $damageOffer ,
     * @param UserIdentity $user
     * @param string $currentRole
     * @return void || throws Exception
     */
    public function processOffer(Request $request, Damage $damage, DamageOffer $damageOffer, UserIdentity $user, string $currentRole): void
    {
        if ($currentRole !== Constants::GUEST_ROLE) {
            $this->validatePermission($user, $damage, $currentRole);
        }
        $this->saveOfferInfo($request, $damage, $damageOffer, $user, $currentRole);
    }

    /**
     * saveDamageInfo
     *
     * function to save damage ticket
     *
     * @param Request $request
     * @param Damage $damage
     * @param DamageOffer $damageOffer
     * @param UserIdentity $user
     * @param string|null $currentRole
     * @return void
     */
    public function saveOfferInfo(Request $request, Damage $damage, DamageOffer $damageOffer, UserIdentity $user, string $currentRole = null): void
    {
        $status = 'COMPANY_GIVE_OFFER_TO_' . strtoupper($currentRole);
        $em = $this->doctrine->getManager();
        $offerFields = $request->get('offerField');
        if (!empty($offerFields)) {
            foreach ($offerFields as $offerField) {
                $damageOfferField = new DamageOfferField();
                $damageOfferField->setLabel($offerField['label']);
                $damageOfferField->setAmount($offerField['amount']);
                $damageOfferField->setOffer($damageOffer);
                $em->persist($damageOfferField);
            }
        }
        $currentActiveOffer = $em->getRepository(DamageOffer::class)->findOneBy(['damage' => $damage, 'company' => $user->getIdentifier(), 'active' => true]);
        if ($currentActiveOffer instanceof DamageOffer) {
            $currentActiveOffer->setActive(false);
//            $offerImage = $this->doctrine->getManager()->getRepository(DamageImage::class)->findOneBy(['damage' => $damage, 'deleted' => 0, 'imageCategory' => $this->parameterBag->get('image_category')['offer_doc']]);
//            if (null !== $offerImage) {
//                $offerImage->setDeleted(true);
//            }
        }
        if ($request->get('damageRequest')) {
            $damageRequest = $em->getRepository(DamageRequest::class)->findOneBy(['publicId' => $request->get('damageRequest')]);
            $damageOffer->setDamageRequest($damageRequest);
            $status = $em->getRepository(DamageStatus::class)->findOneBy(['key' => $status]);
            $damageRequest->setStatus($status);
        }
        if ($request->get('priceSplit')) {
            $encodedString = $request->get('priceSplit');
            $damageOffer->setPriceSplit($encodedString);
        }
        $damageOffer->setAmount($request->get('amount'));
        $damageOffer->setCompany($user);
        $damageOffer->setDamage($damage);
        $damageOffer->setActive(true);
        $damageOffer->setAccepted(false);
        $em->persist($damageOffer);
    }

    /**
     * validatePermission
     *
     * function to validate permission to create an offer
     *
     * @param UserIdentity $user
     * @param Damage $damage
     * @param string|null $currentRole
     * @return bool
     */
    public function validatePermission(UserIdentity $user, Damage $damage, ?string $currentRole = null): bool
    {
        $assignedCompanies = [];
        $damageRequests = $this->doctrine->getRepository(DamageRequest::class)->findBy(['damage' => $damage]);
        foreach ($damageRequests as $request) {
            if ($request->getCompany() instanceof UserIdentity) {
                $assignedCompanies[] = $request->getCompany()->getIdentifier();
            }
        }
        $currentUser = $user->getIdentifier();
        if (!is_null($currentRole) && $currentRole == $this->camelCaseConverter(Constants::COMPANY_USER_ROLE)) {
            $currentUser = $user->getParent()->getIdentifier();
        }
        if (!in_array($currentUser, $assignedCompanies)) {
            throw new AccessDeniedException('noPermission');
        }

        return true;
    }

    /**
     * getStatus
     *
     * function to get Status
     *
     * @param Damage $damage
     * @return string
     */
    public function getStatus(Damage $damage): string
    {
        $companyAssignedUserRole = $this->userService->getUserRoleInObject($damage->getCompanyAssignedBy(), $damage->getApartment());
        if (trim($companyAssignedUserRole) == "") {
            $companyAssignedUserRole = ($damage->getApartment()->getProperty()->getAdministrator() instanceof UserIdentity) ? 'property_admin' : 'owner';
        }

        return 'COMPANY_GIVE_OFFER_TO_' . strtoupper($companyAssignedUserRole);
    }

    /**
     * function to generate offer details array
     *
     * @param DamageOffer $damageOffer ,
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function generateOfferDetails(DamageOffer $damageOffer, Request $request): array
    {
        $data = [];
        $data['offerNumber'] = '#' . $damageOffer->getIdentifier();
        $data['offer'] = $damageOffer->getPublicId();
        $data['damage'] = $damageOffer->getDamage()->getPublicId();
        $data['createdOn'] = $damageOffer->getCreatedAt();
        $data['updatedOn'] = $damageOffer->getUpdatedAt();
        $offerImage = $this->doctrine->getManager()->getRepository(DamageImage::class)->findOneBy(['damage' => $damageOffer->getDamage(), 'deleted' => 0, 'imageCategory' => $this->parameterBag->get('image_category')['offer_doc']]);
        if (null !== $offerImage) {
            $data['attachment'][] = $this->fileUploadHelper->getDamageFileInfo($offerImage, $request->getSchemeAndHttpHost());
        }
//        $total = $damageOffer->getAmount();
//        foreach ($damageOffer->getDamageOfferFields() as $val) {
//            $total += $val->getAmount();
//            $data['customFields'][] = ['label' => $val->getLabel(), 'value' => $val->getAmount()];
//        }
        $data['amount'] = $damageOffer->getAmount();
        $data['splitPrice'] = $damageOffer->getPriceSplit();
        $data['accepted'] = $damageOffer->getAccepted();
        $data['company'] = $this->userService->getUserData($damageOffer->getCompany());


        return $data;
    }

    /**
     * function to rate company
     *
     * @param Request $request
     * @param CompanyRating $companyRating ,
     * @param UserIdentity $user ,
     * @return void
     * @throws \Exception
     */
    public function rateCompany(Request $request, CompanyRating &$companyRating, UserIdentity $user): void
    {
        $em = $this->doctrine->getManager();
        $damage = $em->getRepository(Damage::class)->findOneBy(['publicId' => $request->get('ticket')]);
        $company = $damage->getAssignedCompany();
        $allowedUsers = [$damage->getCompanyAssignedBy(), $damage->getDamageOwner(), $damage->getUser()];
        foreach ($allowedUsers as $allowedUser) {
            if (null !== $allowedUser) {
                $allowedUsers[] = $allowedUser->getAdministrator();
            }
        }
        $tenants = $em->getRepository(PropertyUser::class)->findBy(['object' => $damage->getApartment(), 'deleted' => 0, 'isActive' => 1]);
        foreach ($tenants as $allowedUser) {
            if (null !== $allowedUser) {
                $allowedUsers[] = $allowedUser->getUser();
                $allowedUsers[] = $allowedUser->getUser()->getAdministrator();
            }
        }
        $allowedUsers[] = $damage->getApartment()->getProperty()->getAdministrator();
        $allowedUsers[] = $damage->getApartment()->getProperty()->getJanitor();
        if (!in_array($user, $allowedUsers)) {
            throw new AccessDeniedException('noPermission');
        }
        $existingRating = $em->getRepository(CompanyRating::class)->findOneBy(['damage' => $damage, 'company' => $company]);
        if (null !== $existingRating) {
            $companyRating = $existingRating;
            $companyRating->setRating($request->get('rating'));
            $companyRating->setUpdatedAt(new \DateTime());
        } else {
            $companyRating->setDamage($damage);
            $companyRating->setCompany($company);
        }
        $companyRating->setUser($user);
        $em->persist($companyRating);
    }


    /**
     * function to generate offer details array
     *
     * @param Damage $damage ,
     * @param Request $request
     * @return array
     */
    public function generateRatingDetails(Damage $damage, Request $request): array
    {
        $data = [];
        $em = $this->doctrine->getManager();
        $rating = $em->getRepository(CompanyRating::class)->findOneBy(['damage' => $damage, 'company' => $damage->getAssignedCompany()]);
        if (null !== $rating) {
            $data['publicId'] = $rating->getPublicId();
            $data['createdOn'] = $rating->getCreatedAt();
            $data['ratedBy'] = $this->userService->getUserData($rating->getUser());
            $data['company'] = $this->userService->getUserData($damage->getAssignedCompany());
            $data['rating'] = $rating->getRating();
        }

        return $data;
    }

    /**
     *
     * @param Form $form
     * @param UserIdentity $userIdentity
     * @param UserPasswordHasherInterface $passwordHasher
     * @param string $locale
     * @return void
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \Exception
     */
    public function saveCompanyUser(Form $form, UserIdentity $userIdentity, UserPasswordHasherInterface $passwordHasher, string $locale)
    {
        $param = $this->registrationService->registerUser($form, $userIdentity, $passwordHasher, false, false);
        $this->addCompanyUserRole($userIdentity);
        $this->addUserPermission($userIdentity, $form);
        $userIdentity->setInvitedAt(new \DateTime("now"));
        $this->containerUtility->sendEmailConfirmation($userIdentity, 'CompanyUserRegistrationEmail', $locale, 'Registration', 'company', $param);
    }

    /**
     *
     * @param UserIdentity $userIdentity
     * @return void
     */
    public function addCompanyUserRole(UserIdentity $userIdentity): void
    {
        $userIdentity->addRole($this->doctrine->getManager()->getRepository(Role::class)->findOneBy(['roleKey' => $this->camelCaseConverter($this->parameterBag->get('user_roles')['company_user']), 'deleted' => false]));
    }

    /**
     *
     * @param UserIdentity $userIdentity
     * @param Form $form
     * @return void
     * @throws type
     */
    public function addUserPermission(UserIdentity $userIdentity, Form $form): void
    {
        $data = [];
        foreach ($userIdentity->getUserPermission() as $permission) {
            $data[] = $permission->getPermissionKey();
        }

        $permissions = array_diff($data, $form->get('permission')->getData());
        if (!empty($permissions)) {
            foreach ($permissions as $per) {
                if ($permission = $this->checkIfPermissionKeyExists($per)) {
                    $userIdentity->removeUserPermission($permission);
                }
            }
        }
        foreach ($form->get('permission')->getData() as $permissionKey) {
            if ($permission = $this->checkIfPermissionKeyExists($permissionKey)) {
                if (!$userIdentity->getUserPermission()->contains($permission)) {
                    $userIdentity->addUserPermission($permission);
                }
            }
        }
    }

    /**
     *
     * @param string $key
     * @return Permission
     * @throws InvalidArgumentException
     */
    public function checkIfPermissionKeyExists(string $key): Permission
    {
        $em = $this->doctrine->getManager();
        $permission = $em->getRepository(Permission::class)->findOneBy(['permissionKey' => $key, 'deleted' => false]);
        if (!$permission instanceof Permission) {
            throw new InvalidArgumentException('invalidPermissionKey');
        }
        return $permission;
    }

    /**
     * updateCompanyExpiry
     *
     * @param CompanySubscriptionPlan $subscriptionPlan
     * @param UserIdentity $user
     * @param bool $recurring
     * @param string|null $subscriptionId
     * @return UserIdentity
     * @throws \Exception
     */
    public function updateCompanyExpiry(CompanySubscriptionPlan $subscriptionPlan, UserIdentity $user, bool $recurring = false, string $subscriptionId = null): UserIdentity
    {
        $curDate = new \DateTime();
        $currentDate = new \DateTime($curDate->format('Y-m-d'));
        $expiryDate = $user->getExpiryDate()->format('Y-m-d H:i:s');
        $planEndDate = new \DateTime($expiryDate);
        $expiringDays = $currentDate->diff($planEndDate)->format('%r%a');
        $expiringPeriod = $subscriptionPlan->getPeriod() === 30 ? '+1 month' : '+1 year';
        if (($expiringDays > 0)) {
            $planEndDate = $planEndDate->modify($expiringPeriod);
        } else {
            $planEndDate = $currentDate->modify($expiringPeriod);
        }
        $user->setPlanEndDate($planEndDate);
        $user->setExpiryDate(null);
        if (!is_null($subscriptionId)) {
            $user->setStripeSubscription($subscriptionId);
        }
        $user->setIsExpired(false);
        $user->setCompanySubscriptionPlan($subscriptionPlan);
        if ($subscriptionPlan->getInitialPlan() == 1) {
            $user->setIsFreePlanSubscribed(true);
        }
        $user->setIsRecurring($recurring);

        return $user;
    }

    /**
     *
     * @param CompanySubscriptionPlan $subscription
     * @param ?UserIdentity $user
     * @param string|null $locale
     * @return array
     */
    public function generateCompanyArray(CompanySubscriptionPlan $subscription, ?UserIdentity $user = null, ?string $locale = 'en'): array
    {
        $result["identifier"] = $subscription->getIdentifier();
        $result["publicId"] = $subscription->getPublicId();
        $result["name"] = ($locale == 'de') ? $subscription->getNameDe() : $subscription->getName();
        $result['period'] = ($subscription->getPeriod() == 30) ? $this->translator->trans('monthlyPayment', [], null, $locale) : null;
        $result["initialPlan"] = $subscription->getInitialPlan();
        $result["isFreePlan"] = $subscription->getInitialPlan();
        $result["minPerson"] = $subscription->getMinPerson();
        $result["maxPerson"] = $subscription->getMaxPerson();
        if (!$result["initialPlan"]) {
            $result["amount"] = number_format($subscription->getAmount(), 2, '.', '');
        }
        $result["active"] = $subscription->getActive();
        $result["stripePlan"] = $subscription->getStripePlan();
        $result["inAppPlan"] = $subscription->getInAppPlan();
        $result["inAppAmount"] = $subscription->getInAppAmount();
        if ($user instanceof UserIdentity) {
            $result["expiresOn"] = $user->getExpiryDate();
            $result["cancelledDate"] = $user->getSubscriptionCancelledAt();
            $result["isExpired"] = $user->getIsExpired();
            if ($user->getCompanySubscriptionPlan() === $subscription) {
                $result["isCurrentPlan"] = true;
            }
        }

        $result['details'] = $this->translateDetails($subscription->getDetails(), $subscription, $locale);
        $result['currency'] = $this->params->get('default_currency');
        $result['colorCode'] = $subscription->getColorCode();
        $result['textColor'] = $subscription->getTextColor();

        return $result;
    }

    /**
     *
     * @param UserIdentity $user
     * @param string $locale
     * @return array
     */
    public function getPlans(UserIdentity $user, string $locale): array
    {
        $em = $this->doctrine->getManager();
        $result = [];
        $subscriptions = $em->getRepository(CompanySubscriptionPlan::class)->findBy(['deleted' => 0, 'active' => 1]);
        foreach ($subscriptions as $subscription) {
            $result[] = $this->generateCompanyArray($subscription, $user, $locale);
        }

        return $result;
    }

    /** setPropertyArray
     *
     * @param UserIdentity $user
     * @param Request $request
     * @param string $locale
     * @return array
     */
    public function comparePlans(UserIdentity $user, Request $request, string $locale): array
    {
        $em = $this->doctrine->getManager();
        $data = [];
        $plans = $em->getRepository(CompanySubscriptionPlan::class)->findBy(['deleted' => false, 'active' => 1], ['amount' => 'ASC']);
        foreach ($plans as $key => $plan) {
            if (true == $plan->getInitialPlan() && $plan != $user->getCompanySubscriptionPlan()) {
                continue;
            }
            if ($plan === $user->getCompanySubscriptionPlan()) {
                $data['plans']['currentPlan'] = $this->generateCompanyArray($plan, $user, $locale);
            } else {
                $data['plans']['otherPlans'][] = $this->generateCompanyArray($plan, $user, $locale);
            }
        }

        return array_merge($data);
    }

    /**
     *
     * @param CompanySubscriptionPlan $plan
     * @param UserIdentity $user
     * @param string $locale
     * @return array
     */
    public function getPlanData(CompanySubscriptionPlan $plan, UserIdentity $user, string $locale): array
    {
        if ($user->getCompanySubscriptionPlan() instanceof CompanySubscriptionPlan) {
            $data['currentPlan'] = $this->generateCompanyArray($user->getCompanySubscriptionPlan(), $user, $locale);
        }
        $data['newPlan'] = $this->generateCompanyArray($plan, $user, $locale);
        return $data;
    }

    /**
     *
     * @param array|null $details
     * @param CompanySubscriptionPlan $plan
     * @param string $locale
     * @return array
     */
    private function translateDetails(?array $details, CompanySubscriptionPlan $plan, string $locale): array
    {
        $data = [];
        $userMin = $plan->getMinPerson();
        $userMax = $plan->getMaxPerson();
        if (!empty($details)) {
            foreach ($details as $key => $detail) {
                if (is_array($detail)) {
                    $data[$key] = $this->translateDetails($detail, $plan, $locale);
                } else {
                    $data[$detail] = $this->translator->trans(str_replace(' ', '_', $plan->getName()) . "." . $detail, ['%userMin%' => $userMin, '%userMax%' => $userMax], null, $locale);
                }
            }
        }

        return $data;
    }

    /**
     *
     * @param UserIdentity $userIdentity
     * @param UserIdentity $currentUser
     * @return bool
     */
    public function checkSubscription(UserIdentity $userIdentity, UserIdentity $currentUser): bool
    {
        $currentPlan = $currentUser->getCompanySubscriptionPlan();
        $em = $this->doctrine->getManager();
        $limitStatus = false;
        $currentPersonCount = $em->getRepository(UserIdentity::class)->getActiveCompanyUsers($currentUser, [], true);
        if (true === $currentPlan->getInitialPlan()) {
            $userIdentity->setCompanyUserRestrictedDate(null);
        } else {
            if ($currentPersonCount >= $currentPlan->getmaxPerson()) {
                $userIdentity->setCompanyUserRestrictedDate(new \DateTime());
                $limitStatus = true;
            }
        }
        $em->flush();

        return $limitStatus;
    }

    /**
     *
     * @param UserIdentity $currentUser
     * @param Request $request
     * @return void
     */
    public function activateUsers(UserIdentity $currentUser, Request $request): void
    {
        $em = $this->doctrine->getManager();
        if (!empty($users = $request->get('users'))) {
            foreach ($users as $user) {
                $oUser = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $user, 'parent' => $currentUser, 'deleted' => 0]);
                if (!$oUser instanceof UserIdentity) {
                    throw new ResourceNotFoundException('userNotFound');
                }
                $oUser->setCompanyUserRestrictedDate(null)
                    ->setIsExpired(false);
            }
        }
    }

    /**
     * @param UserIdentity $currentUser
     * @param Request $request
     * @return bool
     */
    public function validateUser(UserIdentity $currentUser, Request $request): bool
    {
        $em = $this->doctrine->getManager();
        $totalActiveUserCount = $em->getRepository(UserIdentity::class)->getActiveCompanyUsers($currentUser, [], true, true);
        $maxUserAllowed = $currentUser->getCompanySubscriptionPlan()->getMaxPerson();
        if (!is_null($maxUserAllowed) && ($maxUserAllowed < ($totalActiveUserCount + 1))) {
            return false;
        }
        return true;
    }

    /**
     *
     * @param UserIdentity $user
     * @param string $locale
     * @return void
     * @throws
     */
    public function resendInvitation(UserIdentity $user, string $locale): void
    {
        $email = $user->getUser()->getProperty();
        $password = $this->validationUtility->generatePassword(8);
        $param['password'] = $password;
        $param['email'] = $email;
        $user->getUser()->setPassword($this->passwordHasher->hashPassword($user->getUser(), $password));
        $this->containerUtility->sendEmailConfirmation($user, 'ResendCompanyRegistration', $locale, 'Invitation', 'individual', $param);
    }

    /**
     * @param array $companyList
     * @param string $iconDir
     * @param string $damage
     * @return array
     */
    public function getCompanyFormattedList(array $companyList, string $iconDir, string $damage): array
    {
        $result = [];
        $em = $this->doctrine->getManager();
        foreach ($companyList as $list) {
            $list['icon'] = $iconDir . $list['icon'];
            $companyObj = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $list['publicId']]);
            $damageObj = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $damage]);
            $request = $em->getRepository(DamageRequest::class)->findOneBy(['company' => $companyObj, 'damage' => $damageObj]);
            $list['alreadyRequested'] = (!$request instanceof DamageRequest) ? false : true;
            array_push($result, $list);
        }

        return $result;
    }

    /**
     * getCompanyUsers
     *
     * @param UserIdentity $user
     * @return array
     */
    public function getCompanyUsers(UserIdentity $user): array
    {
        $data = [];
        $em = $this->doctrine->getManager();
        $currentUsers = $em->getRepository(UserIdentity::class)->getActiveCompanyUsers($user);
        foreach ($currentUsers as $currentUser) {
            $data[]['id'] = $currentUser['publicId'];
            $data[]['status'] = $currentUser['isExpired'];
        }

        return $data;
    }

    /**
     * sendNonRegisteredCompanyEmailNotification
     *
     * @param string $email
     * @param Damage $damage
     * @param string $locale
     * @param string $subject
     * @param string $portalUrl
     * @param bool $isEdit
     * @param string|null $userPublicId
     * @return void
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function sendNonRegisteredCompanyEmailNotification(string $email, Damage $damage, string $locale, string $subject, string $portalUrl, bool $isEdit = false, ?string $userPublicId = null): void
    {
        $this->containerUtility->sendNonRegisteredCompanyEmailNotification($email, $damage, $locale, $subject, $portalUrl, $isEdit, $userPublicId);
    }

    /**
     * @param array $request
     * @param UserPasswordHasherInterface $passwordHasher
     * @param string|null $currentRole
     * @return array
     * @throws \Exception
     */
    public function saveDamageRequest(array $request, UserPasswordHasherInterface $passwordHasher, string $currentRole = null): array
    {
        $em = $this->doctrine->getManager();
        $companies = [];
        $damage = $em->getRepository(Damage::class)->findOneBy(['publicId' => $request['damage']]);
        $status = $em->getRepository(DamageStatus::class)->findOneBy(['key' => $request['status']]);
        foreach ($request['company'] as $company) {
            if (filter_var($company, FILTER_VALIDATE_EMAIL)) {
                $company = $this->userService->handleDisabledCompany($company, $passwordHasher);
            } else {
                $company = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $company]);
            }
            $existingDamageRequest = $em->getRepository(DamageRequest::class)->findOneBy(
                ['damage' => $damage->getIdentifier(), 'company' => $company->getIdentifier()]
            );
            if ((isset($request['comment']) && empty($request['comment'])) && $existingDamageRequest instanceof DamageRequest) {
                continue;
            }
            $companies[] = $company->getIdentifier();
            $damageRequest = new DamageRequest();
            $damageRequest->setDamage($damage);
            $damageRequest->setCompany($company);
            $damageRequest->setStatus($status);
            $damageRequest->setRequestedDate((isset($request['requested_date']) && !empty($request['requested_date'])) ? \DateTime::createFromFormat('Y-m-d H:i', $request['requested_date'] . ' ' . $request['requested_date']) : new \DateTime('now'));
            $damageRequest->setNewOfferRequestedDate((isset($request['new_offer_requested_date']) && !empty($request['new_offer_requested_date'])) ? \DateTime::createFromFormat('Y-m-d H:i', $request['new_offer_requested_date'] . ' ' . $request['new_offer_requested_date']) : null);
            $damageRequest->setComment((isset($request['comment']) && !empty($request['comment'])) ? $request['comment'] : null);
            $em->persist($damageRequest);
            if (isset($request['isEdit']) && $request['isEdit'] == true) {
                $em->getRepository(DamageRequest::class)->updateNewOfferRequestDate($company, $damage, $damageRequest->getIdentifier());
            }
        }
        $em->flush();
        $damage->setStatus($status);
        $damage->setCompanyAssignedBy($damage->getDamageOwner());
        if (!$damage->getCompanyAssignedByRole() instanceof Role) {
//            $assignedByRole = $em->getRepository(Role::class)->findOneBy(['roleKey' => $damage->getCreatedByRole()->getRoleKey()]);
            $assignedByRole = $em->getRepository(Role::class)->findOneBy(['roleKey' => $currentRole]);
            $damage->setCompanyAssignedByRole($assignedByRole);
        }
        $em->flush();

        return $companies;
    }
}
