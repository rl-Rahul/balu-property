<?php


namespace App\Service;

use App\Controller\CompanyController;
use App\Entity\Address;
use App\Entity\Category;
use App\Entity\Directory;
use App\Entity\Document;
use App\Entity\Message;
use App\Entity\Property;
use App\Entity\MessageReadUser;
use App\Entity\User;
use App\Entity\UserIdentity;
use App\Entity\FavouriteCompany;
use App\Entity\UserPropertyPool;
use App\Utils\ValidationUtility;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Uuid as UuidConstraint;
use App\Entity\Interfaces\FavouriteInterface;
use App\Utils\Constants;
use App\Entity\FavouriteIndividual;
use App\Entity\FavouriteAdmin;
use App\Entity\UserSubscription;
use App\Entity\UserDevice;
use Trikoder\Bundle\OAuth2Bundle\Model\AccessToken;
use Trikoder\Bundle\OAuth2Bundle\Model\RefreshToken;
use Trikoder\Bundle\OAuth2Bundle\Security\Authentication\Token\OAuth2Token;
use App\Entity\PushNotification;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Utils\ContainerUtility;
use App\Entity\Apartment;
use App\Entity\PropertyUser;
use App\Entity\Damage;

/**
 * Class UserService
 * @package App\Service
 */
class UserService extends BaseService
{
    /**
     * @var ManagerRegistry $doctrine
     */
    private ManagerRegistry $doctrine;

    /**
     * @var ValidationUtility $validationUtility
     */
    private ValidationUtility $validationUtility;

    /**
     * @var RegistrationService $registrationService
     */
    private RegistrationService $registrationService;

    /**
     * @var SecurityService $securityService
     */
    private SecurityService $securityService;

    /**
     * @var TranslatorInterface $translator
     */

    private TranslatorInterface $translator;

    /**
     * @var ContainerUtility $containerUtility
     */
    private ContainerUtility $containerUtility;

    /**
     * @var DMSService $dmsService
     */
    private DMSService $dmsService;

    /**
     * @var RequestStack $requestStack
     */
    protected RequestStack $requestStack;

    /**
     * UserService constructor.
     * @param ManagerRegistry $doctrine
     * @param ValidationUtility $validationUtility
     * @param RegistrationService $registrationService
     * @param SecurityService $securityService
     * @param TranslatorInterface $translator
     * @param ContainerUtility $containerUtility
     * @param DMSService $dmsService
     * @param RequestStack $requestStack
     */
    public function __construct(
        ManagerRegistry $doctrine,
        ValidationUtility $validationUtility,
        RegistrationService $registrationService,
        SecurityService $securityService,
        TranslatorInterface $translator,
        ContainerUtility $containerUtility,
        DMSService $dmsService,
        RequestStack $requestStack
    )
    {
        $this->doctrine = $doctrine;
        $this->validationUtility = $validationUtility;
        $this->registrationService = $registrationService;
        $this->securityService = $securityService;
        $this->translator = $translator;
        $this->containerUtility = $containerUtility;
        $this->dmsService = $dmsService;
        $this->requestStack = $requestStack;
    }

    /**
     * Set Deleted status
     *
     * @param UserIdentity $userIdentity
     * @param UserIdentity|null $invitor
     * @param Property|null $property
     * @return array
     */
    public function getUserData(UserIdentity $userIdentity, ?UserIdentity $invitor = null, ?Property $property = null): array
    {
        $data['firstName'] = $userIdentity->getFirstName();
        $data['lastName'] = $userIdentity->getLastName();
        $data['email'] = $userIdentity->getUser()->getProperty();
        $data['publicId'] = $userIdentity->getPublicId();
        if ($invitor instanceof UserIdentity && $property instanceof Property) {
            $directoryDetail = $this->doctrine->getRepository(Directory::class)->findOneBy(
                ['invitor' => $invitor, 'user' => $userIdentity, 'deleted' => false, 'property' => $property]);
            if ($directoryDetail instanceof Directory) {
                $data['publicId'] = $directoryDetail->getPublicId();
                $data['firstName'] = $directoryDetail->getFirstName();
                $data['lastName'] = $directoryDetail->getLastName();
                $data['isDirectory'] = true;
            }
        }
        return $data;
    }

    /**
     * Function to get user details
     *
     * @param string $id
     * @param string|null $locale
     * @return array
     * @throws \Exception
     */
    public function getProfile(string $id, ?string $locale = 'en'): array
    {
        //check for valid uuid
        $validator = Validation::createValidator();
        $uuidConstraint = new UuidConstraint();
        $errors = $validator->validate($id, $uuidConstraint);
        if (count($errors) > 0) {
            throw new \Exception('profileDetailsFetchFailed');
        }
        $userIdentity = $this->doctrine->getRepository(UserIdentity::class)->findOneBy(['publicId' => $id]);
        if (!$userIdentity instanceof UserIdentity) {
            throw new UserNotFoundException('userNotFound');
        }
        return $this->getFormattedData($userIdentity, false, $locale);
    }

    /**
     * getFormattedData
     *
     * @param UserIdentity $userIdentity
     * @param bool|null $minData
     * @param string|null $locale
     * @return array
     */
    public function getFormattedData(UserIdentity $userIdentity, ?bool $minData = false, ?string $locale = 'en'): array
    {
        $details['firstName'] = $userIdentity->getFirstName();
        $details['publicId'] = $userIdentity->getPublicId();
        $details['lastName'] = $userIdentity->getLastName();
        $details['companyName'] = $userIdentity->getCompanyName();
        $details['expiryDate'] = $userIdentity->getExpiryDate();
        $details['email'] = $userIdentity->getUser()->getProperty();
        $details['roles'] = $this->securityService->getRoles($userIdentity->getUser()->getProperty());
        $details['roleFormatted'] = implode(', ', array_column($this->securityService->getRoles($userIdentity->getUser()->getProperty()), 'name'));
        $document = $this->doctrine->getRepository(Document::class)->findOneBy(['user' => $userIdentity, 'property' => null, 'apartment' => null, 'type' => 'coverImage', 'isActive' => true]);
        if ($document instanceof Document) {
            $details['document'] = $this->dmsService->getThumbnails($document->getOriginalName(), $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/' . $document->getPath());
            $details['documentPublicId'] = $document->getPublicId();
        }
        if (!$minData) {
            $details['website'] = $userIdentity->getWebsite();
            $details['companyName'] = $userIdentity->getCompanyName();
            $details['addresses'] = $this->getAddressDetails($userIdentity->getAddresses());
            $details['dob'] = $userIdentity->getDob();
            $details['isPolicyAccepted'] = $userIdentity->getIsPolicyAccepted();
            $details['enabled'] = $userIdentity->getEnabled();
            $details['categories'] = $this->getCategories($userIdentity->getCategories());
            $name = ($locale == 'de') ? 'nameDe' : 'name';
            $details['categoriesFormatted'] = implode(', ', array_column($this->getCategories($userIdentity->getCategories()), $name));
        } else {
            $details['address'] = $this->getAddressDetails($userIdentity->getAddresses());
        }

        return $details;
    }

    /**
     * updateUser
     *
     * @param Form $form
     * @param UserIdentity $userIdentity
     * @param bool $isAdminEdit
     * @return void
     * @throws \PhpZip\Exception\ZipException
     * @throws \Exception
     */
    public function updateUser(Form $form, UserIdentity $userIdentity, bool $isAdminEdit = false): void
    {
        $em = $this->doctrine->getManager();
        $email = $form->get('email')->getData();
        if ($this->validationUtility->checkEmailAlreadyExists($email, true)) {
            throw new CustomUserMessageAccountStatusException('userExists');
        }
        $userIdentity->getUser()->setProperty($email);
        $propertyPool = $em->getRepository(UserPropertyPool::class)->findOneBy(['user' => $userIdentity->getUser()]);
        if ($propertyPool instanceof UserPropertyPool) {
            $propertyPool->setProperty($email);
        } else {
            $propertyPool = new UserPropertyPool();
            $propertyPool->setUser($userIdentity->getUser());
            $propertyPool->setProperty($email);
            $propertyPool->setType('email');
            $em->persist($propertyPool);
        }
        $userIdentity->setUpdatedAt(new \DateTime('now'));
        if ($isAdminEdit) {
            $userIdentity->setAdminEditedDate(new \DateTime());
            if ($form->has('category')) {
                $this->registrationService->companyDetails($form, $userIdentity, true, true);
            }
        }
        $em->flush();
        $userAddress = $em->getRepository(Address::class)->findOneBy(['user' => $userIdentity]);
        if (!$userAddress instanceof Address) {
            $userAddress = new Address();
        }
        if ($form->has('role')) {
            if ($form->get('role')->getData() === Constants::COMPANY_ROLE) {
                $this->registrationService->companyDetails($form, $userIdentity, true);
            }
            $this->registrationService->userRoles($form->get('role')->getData(), $userIdentity);
        } elseif ($form->has('category') && !$form->has('role')) {
            $this->registrationService->companyDetails($form, $userIdentity, true, true);
        }
        $this->registrationService->userAddressData($form, $userIdentity, $userAddress, true);
    }

    /**
     * Check logged-in user is administrator for given property
     *
     * @param Property $property
     *
     * @return boolean
     */
    public function isPropertyAdmin(Property $property): bool
    {
        $user = $this->securityService->getUser();
        $userAdmin = $property->getUser()->getAdministrator();
        $propertyUser = $property->getUser();
        $propertyAdmin = $property->getAdministrator();
        if ($userAdmin instanceof UserIdentity || $propertyAdmin instanceof UserIdentity ||
            $propertyUser->getIdentifier() === $user->getIdentifier()) {
            return true;
        }
        return false;
    }

    /**
     * @param Collection $addresses
     * @return array
     */
    public function getAddressDetails(Collection $addresses): array
    {
        $addressDetails = array();
        if (!empty($addresses)) {
            foreach ($addresses as $address) {
                if ($address instanceof Address) {
                    $addressDetails['street'] = $address->getStreet();
                    $addressDetails['streetNumber'] = $address->getStreetNumber();
                    $addressDetails['city'] = $address->getCity();
                    $addressDetails['state'] = $address->getState();
                    $addressDetails['country'] = $address->getCountry();
                    $addressDetails['countryCode'] = $address->getCountryCode();
                    $addressDetails['zipCode'] = $address->getZipCode();
                    $addressDetails['phone'] = $address->getPhone();
                    $addressDetails['landLine'] = $address->getLandLine();
                    $addressDetails['latitude'] = $address->getLatitude();
                    $addressDetails['longitude'] = $address->getLongitude();
                }
            }
        }

        return $addressDetails;
    }

    /**
     * @param UserIdentity $user
     * @param string|null $currentRole
     * @return array
     */
    public function getUserRoles(UserIdentity $user, string $currentRole = null): array
    {
        $locale = $this->requestStack->getCurrentRequest()->headers->get('locale');
        $locale = !is_null($locale) ? $locale : 'en';
        $data = [];
        $em = $this->doctrine->getManager();
        $roles = $em->getRepository(UserIdentity::class)->getUserRoles($user->getIdentifier(), $locale);
        foreach ($roles as $key => $role) {
            if ($role['roleKey'] === $currentRole) {
                $roles[$key]['isCurrent'] = true;
                $data['roles'] = $roles;
            } elseif (empty($role['roleKey'])) {
                $data['roles'] = array(['name' => 'user', 'roleKey' => 'user', 'sortOrder' => 1]);
            } else {
                $data['roles'] = $roles;
            }
        }
        $data['language'] = $user->getLanguage() ? $user->getLanguage() : $locale;
        return $data;
    }

    /**
     * @param Collection $categories
     * @return array
     */
    public function getCategories(Collection $categories): array
    {
        $categoriesArray = array();
        if (!empty($categories)) {
            foreach ($categories as $key => $category) {
                if ($category instanceof Category) {
                    $categoriesArray[$key]['publicId'] = $category->getPublicId();
                    $categoriesArray[$key]['name'] = $category->getName();
                    $categoriesArray[$key]['nameDe'] = $category->getNameDe();
                }
            }
        }

        return $categoriesArray;
    }

    /**
     * getUserIdentity
     *
     * @param User|null $user
     * @return object|UserIdentity|null
     */
    public function getUserIdentity(?User $user): ?UserIdentity
    {
        $identity = $this->doctrine->getManager();
        return $identity->getRepository(UserIdentity::class)->findOneBy(['user' => $user]);
    }

    /**
     * @param UserIdentity $user
     * @param string|null $currentUserRole
     * @return string|null
     */
    public function getCurrentUserRole(UserIdentity $user, ?string $currentUserRole): ?string
    {
        $userRoles = $this->getUserRoles($user, $currentUserRole);
        $roleKey = isset($userRoles['roles']) ? array_column($userRoles['roles'], 'roleKey') : [];
        if (in_array($currentUserRole, $roleKey) || in_array($this->snakeToCamelCaseConverter($currentUserRole), $roleKey)) {
            return $currentUserRole;
        }

        return null;
    }

    /**
     * @param UserIdentity $favouriteUser
     * @param UserIdentity $user
     * @param string $role
     * @return string $role
     * @throws \Exception
     */
    public function processFavourite(UserIdentity $favouriteUser, UserIdentity $user, string $role): string
    {
        $em = $this->doctrine->getManager();
        $favourite = null;
        switch ($role) {
            case Constants::FAVOURITES_ROLES[0]:
                $favourite = $em->getRepository(FavouriteIndividual::class)->findOneBy(['favouriteIndividual' => $favouriteUser, 'user' => $user]);
                $entity = 'App\Entity\FavouriteIndividual';
                $userProperty = 'User';
                $favouriteUserProperty = 'FavouriteIndividual';
                break;

            case Constants::FAVOURITES_ROLES[1]:
                $favourite = $em->getRepository(FavouriteCompany::class)->findOneBy(['favouriteCompany' => $favouriteUser, 'user' => $user]);
                $entity = 'App\Entity\FavouriteCompany';
                $userProperty = 'User';
                $favouriteUserProperty = 'FavouriteCompany';
                break;

            case Constants::FAVOURITES_ROLES[2]:
                $favourite = $em->getRepository(FavouriteAdmin::class)->findOneBy(['favouriteAdmin' => $favouriteUser, 'user' => $user]);
                $entity = 'App\Entity\FavouriteAdmin';
                $userProperty = 'User';
                $favouriteUserProperty = 'FavouriteAdmin';
                break;
            default:
                throw new \Exception('invalidRole');
        }

        if ($favourite instanceof $entity) {
            $this->unFavouriteUser($favourite);
            $successMessage = 'unfavourited';
        } else {
            $this->favouriteUser($user, $favouriteUser, $entity, $userProperty, $favouriteUserProperty);
            $successMessage = 'favourited';
        }
        return $successMessage;
    }

    /**
     * Function to implement favourite functionality.
     *
     * @param UserIdentity $user
     * @param UserIdentity $favouriteUser
     * @param string $favaouriteEntity | entity class which implements FavouriteInterface
     * @param string $userProperty
     * @param string $favouriteUserProperty
     * @return bool
     */
    public function favouriteUser(UserIdentity $user, UserIdentity $favouriteUser, string $favaouriteEntity, string $userProperty, string $favouriteUserProperty): bool
    {
        $em = $this->doctrine->getManager();
        if (!$em->getRepository($favaouriteEntity)->findOneBy(['deleted' => false, 'user' => $user, lcfirst($favouriteUserProperty) => $favouriteUser])) {
            //create entity set methods
            $favaouriteEntity = new $favaouriteEntity();
            $favouriteUserMethod = 'set' . $favouriteUserProperty;
            $userMethod = 'set' . $userProperty;
            $favaouriteEntity->$favouriteUserMethod($favouriteUser);
            $favaouriteEntity->$userMethod($user);
            $em->persist($favaouriteEntity);
            $em->flush();
            return true;
        }
    }

    /**
     * @param FavouriteInterface $favourite | entity class which implements FavouriteInterface
     * @return bool
     */
    public function unFavouriteUser(FavouriteInterface $favourite): bool
    {
        $em = $this->doctrine->getManager();
        $em->remove($favourite);
        $em->flush();
        return true;
    }

    /**
     * Function to get parent role
     *
     * @param string $role
     * @return string
     */
    public function getParentRole(string $role): string
    {
        foreach (Constants::INHERITED_ROLES as $key => $value) {
            if ($role === $key) {
                $role = $value;
            }
        }

        return $role;
    }

    /**
     * Function to check if use subscription plan is expired
     *
     * @param UserIdentity $user
     * @return bool
     */
    public function checkSubscriptionIsExpired(UserIdentity $user): bool
    {
        $return = false;
        $userSubscription = $this->doctrine->getRepository(UserSubscription::class)->findOneBy(['user' => $user]);
        if (null !== $userSubscription) {
            $return = $userSubscription->getIsExpired();
        }

        return $return;
    }

    /**
     * function to get user device id as array
     *
     * @param UserIdentity $user
     * @return array
     */
    public function getDeviceIds(UserIdentity $user): array
    {
        return $this->doctrine->getRepository(UserDevice::class)->userDeviceList($user);
    }

    /**
     * Function to revoke Access and Refresh Tokens
     *
     * @param UserIdentity $user
     * @param bool $logoutEverywhere
     * @return void
     */
    public function revokeTokens(UserIdentity $user, OAuth2Token $token, bool $logoutEverywhere): void
    {
        $em = $this->doctrine->getManager();
        if (!$logoutEverywhere) {
            $tokenId = $token->getAttributes()['server_request']->getAttributes()['oauth_access_token_id'];
            $criteria = ['identifier' => $tokenId];
        } else {
            $criteria = ['userIdentifier' => $user->getUser()->getProperty(), 'revoked' => false];
        }
        $accessTokens = $em->getRepository(AccessToken::class)->findBy($criteria);
        foreach ($accessTokens as $accessToken) {
            $accessToken->revoke();
            $em->persist($accessToken);
            $refreshToken = $em->getRepository(RefreshToken::class)->findOneBy(['accessToken' => $accessToken]);
            $refreshToken->revoke();
            $em->persist($refreshToken);
        }

        return;
    }

    /**
     * Function to delete user device details
     *
     * @param UserIdentity $user
     * @param bool $logoutEverywhere
     * @param string|null $deviceId
     * @return void
     */
    public function removeUserDeviceDetails(UserIdentity $user, bool $logoutEverywhere, ?string $deviceId = null): void
    {
        if (!$logoutEverywhere && null === $deviceId) {
            return;
        }
        $em = $this->doctrine->getManager();
        $criteria = ['user' => $user->getUser(), 'deleted' => 0];
        if (null !== $deviceId) {
            $criteria['deviceId'] = $deviceId;
        }
        $userDeviceObjs = $em->getRepository(UserDevice::class)->findBy($criteria);
        foreach ($userDeviceObjs as $userDeviceObj) {
            $userDeviceObj->setDeleted(true);
            $em->persist($userDeviceObj);
        }

        return;
    }

    /**
     * Function to return and format user's notification list
     *
     * @param Request $request
     * @param string $currentRole
     * @param UserIdentity $user
     * @param string $locale
     * @return array
     */
    public function getUserNotifications(Request $request, string $currentRole, UserIdentity $user, string $locale): array
    {
        $em = $this->doctrine->getManager();
        $count = $request->get('limit');
        $userNotifications = $em->getRepository(PushNotification::class)->getUserNotifications($user, $currentRole, $count, $request->get('offset'), $request->get('filter'));
        $notificationList['totalRowCount'] = $em->getRepository(PushNotification::class)->getTotalRowCount($user, $currentRole);
        $notificationList['totalReadCount'] = $em->getRepository(PushNotification::class)->getTotalReadCount($user, $currentRole);
        foreach ($userNotifications as $listItem) {
            $data['publicId'] = (string)$listItem['notificationId'];
            $data['isRead'] = $listItem['isRead'];
            $data['message'] = $this->getMessageContent($listItem, $locale);
            $data['url'] = $this->containerUtility->getEventUrl($listItem['event'], $listItem['damageId']);
            $data['damageId'] = (string)$listItem['damageId'];
            $data['userRole'] = $listItem['roleKey'];
            $notificationList['rows'][] = $data;
        }

        return $notificationList;
    }

    /**
     * @param array $listItem
     * @param string|null $locale
     * @return string
     */
    public function getMessageContent(array $listItem, ?string $locale = 'en'): string
    {
        return $this->translator->trans(
            (($locale == 'de') && !empty($listItem['messageDe'])) ? $listItem['messageDe'] : $listItem['message'],
            [],
            null,
            $locale
        );
    }

    /**
     * Function to change read status of notifications
     *
     * @param Request $request
     * @param UserIdentity $user
     * @return void
     */
    public function changeNotificationReadStatus(Request $request, UserIdentity $user): void
    {
        $em = $this->doctrine->getManager();
        foreach ($request->request->get('notificationId') as $notification) {
            $oNotification = $em->getRepository(PushNotification::class)->findOneBy(['publicId' => $notification, 'toUser' => $user]);
            $oNotification->setReadMessage($request->request->get('isRead'));
        }
        $em->flush();

        return;
    }

    /**
     * Function to get user role in object
     *
     * @param UserIdentity $user
     * @param Apartment $object
     * @return string
     */
    public function getUserRoleInObject(UserIdentity $user, Apartment $object): string
    {
        $em = $this->doctrine->getManager();
        $userRole = '';
        $property = $object->getProperty();
        $allRoles = $this->containerUtility->getParameterBag()->get('user_roles');
        if ($property->getUser() === $user) {
            $userRole = $allRoles['owner'];
        } elseif ($property->getAdministrator() === $user) {
            $userRole = $allRoles['property_admin'];
        } elseif ($property->getJanitor() === $user) {
            $userRole = $allRoles['janitor'];
        } elseif ($em->getRepository(PropertyUser::class)->checkIfUserHasActiveRole($object->getId(), $user->getId(), $allRoles['object_owner']) instanceof PropertyUser) {
            $userRole = $allRoles['object_owner'];
        } elseif ($em->getRepository(PropertyUser::class)->checkIfUserHasActiveRole($object->getId(), $user->getId(), $allRoles['tenant']) instanceof PropertyUser) {
            $userRole = $allRoles['tenant'];
        }

        return $userRole;
    }

    /**
     * getUserRoleInDamage
     *
     * function to get damage role
     *
     * @param UserIdentity $user
     * @param Damage $damage
     *
     * @return string
     */
    public function getUserRoleInDamage(UserIdentity $user, Damage $damage): string
    {
        $object = $damage->getApartment();
        if ($damage->getAssignedCompany() === $user) {
            $userRole = $this->containerUtility->getParameterBag()->get('user_roles')['company'];
        } else {
            $userRole = $this->getUserRoleInObject($user, $object);
        }

        return $userRole;
    }

    /**
     * handleDisabledCompany
     *
     * function to save and return disabled company
     *
     * @param string $email
     * @param UserPasswordHasherInterface $passwordHasher
     * @return UserIdentity
     * @throws \Exception
     */
    public function handleDisabledCompany(string $email, UserPasswordHasherInterface $passwordHasher): UserIdentity
    {
        $em = $this->doctrine->getManager();
        if ($this->validationUtility->checkEmailAlreadyExists($email)) {
            return $em->getRepository(UserIdentity::class)->findOneByEmail($email);
        }
        return $this->createDisabledCompany($email, $passwordHasher);
    }

    /**
     * createDisableCompany
     *
     * function to save and return disabled company
     *
     * @param string $email
     * @param UserPasswordHasherInterface $passwordHasher
     * @return UserIdentity
     * @throws \Exception
     */
    public function createDisabledCompany(string $email, UserPasswordHasherInterface $passwordHasher): UserIdentity
    {
        return $this->registrationService->createDisabledCompany($email, $passwordHasher);
    }

    /**
     * @param UserIdentity $userIdentity
     * @param string $language
     * @return bool
     */
    public function updateUserLanguage(UserIdentity $userIdentity, string $language): bool
    {
        $userIdentity->setLanguage($language);

        return true;
    }

    /**
     * checkIfIntendedUser
     *
     * @param string|null $userPublicId |null
     * @return UserIdentity
     */
    public function checkIfIntendedUser(?string $userPublicId = null): ?UserIdentity
    {
        $user = $this->doctrine->getRepository(UserIdentity::class)->findOneBy(['identifier' => $userPublicId]);
        if ($user instanceof UserIdentity) {
            return $user;
        }
        return null;
    }

    /**
     * getUsersDeviceListForSendingMessages
     *
     * @param Message $message
     * @return array
     */
    public function getUsersDeviceListForSendingMessages(Message $message): array
    {
        $details = $deviceId = $result = [];
        $messageList = $this->doctrine->getRepository(MessageReadUser::class)->findBy(['message' => $message->getIdentifier()]);
        foreach ($messageList as $key => $message) {
            $details[$key]['user'] = $message->getUser()->getIdentifier();
            $details[$key]['role'][] = $message->getRole()->getRoleKey();
            $details[$key]['deviceList'][] = $this->doctrine->getRepository(UserDevice::class)->userDeviceList($message->getUser());
        }
        foreach ($details as $detail) {
            $userId = $detail['user'];
            $roles = $detail['role'];
            $deviceList = array_merge(...$detail['deviceList']);
            if (!isset($result[$userId])) {
                $result[$userId] = [
                    'deviceList' => [],
                    'roles' => []
                ];
            }

            $result[$userId]['deviceList'] = array_merge($result[$userId]['deviceList'], $deviceList);
            $result[$userId]['roles'] = array_merge($result[$userId]['roles'], $roles);
        }

        return $result;
    }
}
