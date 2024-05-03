<?php


namespace App\Service;


use App\Entity\Address;
use App\Entity\Apartment;
use App\Entity\Directory;
use App\Entity\Document;
use App\Entity\ObjectContracts;
use App\Entity\Property;
use App\Entity\PropertyRoleInvitation;
use App\Entity\PropertyUser;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserIdentity;
use App\Utils\Constants;
use App\Utils\ValidationUtility;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Google\Service\Sheets\ThemeColorPair;
use PhpParser\Node\Scalar\MagicConst\Dir;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Form;
use App\Utils\ContainerUtility;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Utils\GeneralUtility;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class PropertyService
 * @package App\Service
 */
class DirectoryService extends BaseService
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
     * @var ValidationUtility $validationUtility
     */
    private ValidationUtility $validationUtility;

    /**
     * @var RegistrationService $registrationService
     */
    private RegistrationService $registrationService;

    /**
     * @var UserService $userService
     */
    private UserService $userService;

    /**
     * @var SecurityService $securityService
     */
    private SecurityService $securityService;

    /**
     * @var PropertyService $propertyService
     */
    private PropertyService $propertyService;

    /**
     *
     * @var UserPasswordHasherInterface
     */
    private UserPasswordHasherInterface $passwordHasher;

    /**
     *
     * @var ObjectService $objectService
     */
    private ObjectService $objectService;

    /**
     * UserService constructor.
     * @param ManagerRegistry $doctrine
     * @param ContainerUtility $containerUtility
     * @param ParameterBagInterface $params
     * @param GeneralUtility $generalUtility
     * @param TranslatorInterface $translator
     * @param ValidationUtility $validationUtility
     * @param RegistrationService $registrationService
     * @param UserService $userService
     * @param SecurityService $securityService
     * @param PropertyService $propertyService
     * @param UserPasswordHasherInterface $passwordHasher
     * @param ObjectService $objectService
     */
    public function __construct(ManagerRegistry $doctrine, ContainerUtility $containerUtility, ParameterBagInterface $params,
                                GeneralUtility $generalUtility, TranslatorInterface $translator, ValidationUtility $validationUtility,
                                RegistrationService $registrationService, UserService $userService, SecurityService $securityService, PropertyService $propertyService,
                                UserPasswordHasherInterface $passwordHasher, ObjectService $objectService)
    {
        $this->doctrine = $doctrine;
        $this->containerUtility = $containerUtility;
        $this->params = $params;
        $this->generalUtility = $generalUtility;
        $this->translator = $translator;
        $this->validationUtility = $validationUtility;
        $this->registrationService = $registrationService;
        $this->userService = $userService;
        $this->securityService = $securityService;
        $this->propertyService = $propertyService;
        $this->passwordHasher = $passwordHasher;
        $this->objectService = $objectService;
    }

    /**
     * Method to create individual user
     *
     * @param Form $form
     * @param UserIdentity $userIdentity
     * @param UserPasswordHasherInterface $passwordHasher
     * @param UserIdentity $invitedBy
     * @param string $locale
     * @param Request $request
     * @return Directory|null
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \Exception
     */
    public function saveIndividual(Form $form, UserIdentity $userIdentity, UserPasswordHasherInterface $passwordHasher, UserIdentity $invitedBy, string $locale, Request $request): ?Directory
    {
        $isSystemGeneratedEmail = $form->has('isSystemGeneratedEmail') && true === $form->get('isSystemGeneratedEmail')->getData();
        $param = $this->registrationService->registerUser($form, $userIdentity, $passwordHasher, false, $isSystemGeneratedEmail);
        $this->doctrine->getManager()->refresh($userIdentity);
        if ($form->has('sendInvite') && true === $form->get('sendInvite')->getData() && !$isSystemGeneratedEmail) {
            $this->containerUtility->sendEmailConfirmation($userIdentity, 'IndividualRegistration', $locale, 'Registration', 'individual', $param);
        }
        $property = $this->doctrine->getRepository(Property::class)->findOneBy(['publicId' => $form->get('property')->getData()]);
        $janitorInvite = false;
        if ($form->has('janitorInvite') && true === $form->get('janitorInvite')->getData() && !$isSystemGeneratedEmail) {
            $janitorInvite = true;
            $validateJanitorStatus = $this->propertyService->checkJanitorAvailableOrInvited($property);
            if (gettype($validateJanitorStatus) !== 'boolean') {
                throw new InvalidArgumentException($validateJanitorStatus);
            }
        }
        $this->propertyService->addAdministratorInvitation($userIdentity, $invitedBy, $property, $janitorInvite, null, $request->request->get('sendInvite'));
        return $this->saveInvitationDetails($userIdentity, $invitedBy, $request);
    }

    /**
     * Method to save invitation details
     *
     * @param UserIdentity $user
     * @param UserIdentity $invitor
     * @param Request $request
     * @return Directory|null
     * @throws \Exception
     */
    public function saveInvitationDetails(UserIdentity $user, UserIdentity $invitor, Request $request): ?Directory
    {
        $em = $this->doctrine->getManager();
        $cond = ['invitor' => $invitor, 'user' => $user, 'deleted' => false];
        $property = $em->getRepository(Property::class)->findOneBy(['publicId' => $request->request->get('property')]);
        if ($property instanceof Property) {
            $cond += ['property' => $property->getIdentifier()];
        }
        $directory = $em->getRepository(Directory::class)->findOneBy($cond);
        if (!$directory instanceof Directory) {
            $directory = new Directory();
        }
        $directory->setInvitor($invitor)
            ->setUser($user);
        !empty($request->get('firstName')) ? $directory->setFirstName($request->get('firstName')) : '';
        !empty($request->get('lastName')) ? $directory->setLastName($request->get('lastName')) : '';
        !empty($request->get('street')) ? $directory->setStreet($request->get('street')) : '';
        !empty($request->get('streetNumber')) ? $directory->setStreetNumber($request->get('streetNumber')) : '';
        !empty($request->get('city')) ? $directory->setCity($request->get('city')) : '';
        !empty($request->get('zipCode')) ? $directory->setZipCode($request->get('zipCode')) : '';
        !empty($request->get('country')) ? $directory->setCountry($request->get('country')) : '';
        !empty($request->get('property')) ? $directory->setProperty($property) : '';
        !empty($request->get('phone')) ? $directory->setPhone($request->get('phone')) : '';
        !empty($request->get('dob')) ? $directory->setDob(new \DateTime($request->get('dob'))) : '';
        !empty($request->get('landline')) ? $directory->setLandline($request->get('landline')) : '';
        !empty($request->get('companyName')) ? $directory->setCompanyName($request->get('companyName')) : '';
        !empty($request->get('countryCode')) ? $directory->setCountryCode($request->get('countryCode')) : '';
        $em->persist($directory);

        if ($request->request->has('janitorInvite') && true === $request->request->get('janitorInvite') &&
            $request->request->has('sendInvite') && !$request->request->get('sendInvite')) {
            $property->setJanitor($user);
        }
        $em->flush();

        return $directory;
    }

    /**
     * @param User $user
     * @param Request $request
     * @param UserIdentity $currentUser
     * @return array
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \Exception
     */
    public function addExistingUserToDirectory(User $user, Request $request, UserIdentity $currentUser): array
    {
        $userIdentity = $this->doctrine->getRepository(UserIdentity::class)->findOneBy(['user' => $user, 'deleted' => false]);
        if (null !== $request->request->get('role')) {
            $userIdentity->addRole($this->doctrine->getManager()->getRepository(Role::class)->findOneBy(
                ['roleKey' => $this->camelCaseConverter($request->request->get('role')), 'deleted' => false]));
        }
        $property = $this->doctrine->getRepository(Property::class)->findOneBy(
            ['publicId' => $request->request->get('property')]);
        $janitorInvite = false;
        if ($request->request->has('janitorInvite') && true === $request->request->get('janitorInvite')) {
            $janitorInvite = true;
            $validateJanitorStatus = $this->propertyService->checkJanitorAvailableOrInvited($property);
            if (gettype($validateJanitorStatus) !== 'boolean') {
                throw new InvalidArgumentException($validateJanitorStatus);
            }
        }
        $this->propertyService->addAdministratorInvitation($userIdentity, $currentUser, $property, $janitorInvite, null, $request->request->get('sendInvite'));
        $directory = $this->saveInvitationDetails($userIdentity, $currentUser, $request);
        if ($directory instanceof Directory) {
            if ($request->request->get('sendInvite')) {
                $directory->setInvitedAt(new \DateTime("now"));
                $locale = $request->headers->has('locale') ? $request->headers->get('locale') : 'en';
                $subject = $this->translator->trans('invitation');
                if (is_null($directory->getUser()->getUser()->getFirstLogin())) {
                    $this->containerUtility->sendEmailConfirmation($userIdentity, 'IndividualInvitation', $locale, $subject, null, []);
                }
            }
            $data = $this->generalUtility->handleSuccessResponse('inviteSuccessfull', $this->userService->getUserData($userIdentity, $currentUser, $property));
        } else {
            $data = $this->generalUtility->handleFailedResponse('alreadyInvited');
        }
        return $data;
    }

    /**
     * Get admin and janitor directories
     *
     * @param UserIdentity $user
     * @param string|null $type
     * @param string|null $locale
     * @return array
     */
    public function getDirectories(UserIdentity $user, ?string $type = null, ?string $locale = 'en'): array
    {
        $data = array();
        $em = $this->doctrine->getManager();
        $userDirectory = $em->getRepository(UserIdentity::class)->getUserList($user, $type);
        if (!empty($userDirectory)) {
            foreach ($userDirectory as $key => $directory) {
                $data[$key]['publicId'] = $directory['publicId'];
                $data[$key]['name'] = (isset($directory['companyName']) && !empty(isset($directory['companyName']))) ? $directory['companyName'] :
                    ((isset($directory['nameDir']) && !empty($directory['nameDir'])) ? $directory['nameDir'] : $directory['name']);
                $data[$key]['firstName'] = $directory['firstName'];
                $data[$key]['lastName'] = $directory['lastName'];
                $data[$key]['companyName'] = $directory['companyName'];
                $data[$key]['email'] = $directory['email'];
                $data[$key]['isFavourite'] = $directory['isFavourite'];
                $data[$key]['isSystemGeneratedEmail'] = $directory['isSystemGeneratedEmail'];
                $data[$key]['isRegisteredUser'] = !is_null($directory['firstLogin']);
                $rolesArray = $this->getDirectoryRoles($directory['userId'], $locale);
                usort($rolesArray, function ($a, $b) {
                    return ($a['key'] <=> $b['key']) || ($a['name'] <=> $b['name']);
                });
                $uniqueArray = array_map("unserialize", array_unique(array_map("serialize", $rolesArray)));
                $uniqueArray = array_values($uniqueArray);
                $data[$key]['role'] = $uniqueArray;
            }
        }
        return $data;
    }

    /**
     * Get directory roles
     *
     * @param string $userId
     * @param string|null $locale
     * @return array
     */
    private function getDirectoryRoles(string $userId, ?string $locale): array
    {
        $em = $this->doctrine->getManager();
        $role = [];
        $propertyUserRoles = $em->getRepository(PropertyUser::class)->findBy(['user' => $userId]);
        foreach ($propertyUserRoles as $userRole) {
            if ($userRole instanceof PropertyUser && $userRole->getRole() instanceof Role) {
                $role[] = [
                    'key' => $userRole->getRole()->getRoleKey(),
                    'name' => ($locale == 'de') ? $userRole->getRole()->getNameDe() : $userRole->getRole()->getName()
                ];
            }
        }
        $userIdentityRoles = $em->getRepository(UserIdentity::class)->findOneByIdentifier($userId)->getRole();
        foreach ($userIdentityRoles as $userRole) {
            $role[] = [
                'key' => $userRole->getRoleKey(),
                'name' => ($locale == 'de') ? $userRole->getNameDe() : $userRole->getName()
            ];
        }
        return $role;
    }

    /**
     * @param UserIdentity $userIdentity
     * @param string $parameter
     * @param string $currentRole
     * @param string|null $locale
     * @return array
     */
    public function searchIndividual(UserIdentity $userIdentity, string $parameter, string $currentRole, ?string $locale = 'en'): array
    {
        $em = $this->doctrine->getManager();
        $list = $em->getRepository(UserIdentity::class)->getUserList($userIdentity, $parameter, false, $this->snakeToCamelCaseConverter($currentRole));
        return array_map(function ($users) use ($locale) {
            $users['isRegisteredUser'] = (bool)$users['isRegisteredUser'];
            $rolesArray = $this->getDirectoryRoles($users['userId'], $locale);
            usort($rolesArray, function ($a, $b) {
                return ($a['key'] <=> $b['key']) || ($a['name'] <=> $b['name']);
            });
            $uniqueArray = array_map("unserialize", array_unique(array_map("serialize", $rolesArray)));
            $uniqueArray = array_values($uniqueArray);
            $users['role'] = $uniqueArray;
            return $users;
        }, $list);
    }

    /**
     *
     * @param Request $request
     * @param object $user
     * @param UserIdentity $currentUser
     * @param string $locale
     * @param string $currentRole
     * @param string|null $property
     * @return array
     * @throws \Exception
     */
    public function getUserDetail(Request $request, object $user, UserIdentity $currentUser, string $locale, string $currentRole, ?string $property = null): array
    {
        $finalList = [];
        $em = $this->doctrine->getManager();
        if ($user instanceof Directory) {
            $finalList['details']['invitedOn'] = $user->getInvitedAt();
            $finalList['details']['firstName'] = $user->getFirstName();
            $finalList['details']['lastName'] = $user->getLastName();
            $finalList['details']['directoryFirstName'] = $user->getFirstName();
            $finalList['details']['directoryLastName'] = $user->getLastName();
            $finalList['details']['companyName'] = $user->getCompanyName();
            $finalList['details']['name'] = $user->getCompanyName() ? $user->getCompanyName() : $user->getFirstName() . ' ' . $user->getLastName();
            $finalList['details']['email'] = $user->getUser()->getUser()->getProperty();
            $finalList['details']['dob'] = $user->getDob();
            $finalList['details']['street'] = $user->getStreet();
            $finalList['details']['streetNumber'] = $user->getStreetNumber();
            $finalList['details']['country'] = $user->getCountry();
            $finalList['details']['city'] = $user->getCity();
            $finalList['details']['zipCode'] = $user->getZipCode();
            $finalList['details']['state'] = $user->getState();
            $finalList['details']['phone'] = $user->getPhone();
            $finalList['details']['landLine'] = $user->getLandline();
            $finalList['details']['countryCode'] = $user->getCountryCode();
            $user = $user->getUser();
        } else {
            $finalList['details']['invitedOn'] = $user->getInvitedAt();
            $finalList['details']['firstName'] = $user->getFirstName();
            $finalList['details']['lastName'] = $user->getLastName();
            $finalList['details']['companyName'] = $user->getCompanyName();
            $finalList['details']['email'] = $user->getUser()->getProperty();
            $finalList['details']['dob'] = $user->getDob();
        }
        $finalList['details']['language'] = $user->getLanguage();
        $finalList['details']['isRegisteredUser'] = !is_null($user->getUser()->getFirstLogin());
        $finalList['details']['lastLogin'] = $user->getUser()->getLastLogin();
        $finalList['details']['firstLogin'] = $user->getUser()->getFirstLogin();
        $finalList['details']['isSystemGeneratedEmail'] = $user->getIsSystemGeneratedEmail();
        $address = $em->getRepository(Address::class)->findOneBy(['user' => $user, 'deleted' => 0]);
        if ($address instanceof Address) {
            $finalList['details']['phone'] = isset($finalList['details']['phone']) ?
                $finalList['details']['phone'] : $address->getPhone();
            $finalList['details']['landLine'] = isset($finalList['details']['landLine']) ?
                $finalList['details']['landLine'] : $address->getLandLine();
            $finalList['details']['street'] = isset($finalList['details']['street']) ?
                $finalList['details']['street'] : $address->getStreet();
            $finalList['details']['streetNumber'] = isset($finalList['details']['streetNumber']) ?
                $finalList['details']['streetNumber'] : $address->getStreetNumber();
            $finalList['details']['country'] = isset($finalList['details']['country']) ?
                $finalList['details']['country'] : $address->getCountry();
            $finalList['details']['city'] = isset($finalList['details']['city']) ?
                $finalList['details']['city'] : $address->getCity();
            $finalList['details']['latitude'] = $address->getLatitude();
            $finalList['details']['longitude'] = $address->getLongitude();
            $finalList['details']['state'] = isset($finalList['details']['state']) ?
                $finalList['details']['state'] : $address->getState();
            $finalList['details']['zipCode'] = isset($finalList['details']['zipCode']) ?
                $finalList['details']['zipCode'] : $address->getZipCode();
            $finalList['details']['countryCode'] = isset($finalList['details']['countryCode']) ?
                $finalList['details']['countryCode'] : $address->getCountryCode();
        }
        $finalList['allocations']['property'] = $this->getPropertyAllocationDetails($user);
        if (!is_null($property)) {
            $property = $em->getRepository(Property::class)->findOneBy(['publicId' => $property]);
            if (!$property instanceof Property)
                throw new EntityNotFoundException('invalidProperty');
            $finalList['allocations']['object'] = $this->objectService->getObjects($property, $request, $locale, $user);
            $janitorInvitationStatus = $em->getRepository(PropertyRoleInvitation::class)
                ->checkJanitorInvitationStatus($property->getIdentifier(), Constants::JANITOR_ROLE, $user);
            $finalList['details']['isJanitorInvited'] = $janitorInvitationStatus instanceof PropertyRoleInvitation;
        }

        return $finalList;
    }

    /**
     * @param Form $form
     * @param Directory $directory
     * @return void
     * @throws \Exception
     */
    public function updateIndividualDetails(Form $form, Directory $directory): void
    {
        $directory->setFirstName($form->get('firstName')->getData() ? $form->get('firstName')->getData() : $directory->getFirstName());
        $directory->setLastName($form->get('lastName')->getData() ? $form->get('lastName')->getData() : $directory->getLastName());
        $directory->setStreet($form->get('street')->getData() ? $form->get('street')->getData() : $directory->getStreet());
        $directory->setStreetNumber($form->get('streetNumber')->getData() ? $form->get('streetNumber')->getData() : $directory->getStreetNumber());
        $directory->setCity($form->get('city')->getData() ? $form->get('city')->getData() : $directory->getCity());
        $directory->setZipCode($form->get('zipCode')->getData() ? $form->get('zipCode')->getData() : $directory->getZipCode());
        $directory->setCountry($form->get('country')->getData() ? $form->get('country')->getData() : $directory->getCountry());
        $directory->setPhone($form->get('phone')->getData() ? $form->get('phone')->getData() : $directory->getPhone());
        if ($form->has('dob') && $form->get('dob')->getData() instanceof \DateTime) {
            $dob = new \DateTime($form->get('dob')->getData()->format('Y-m-d'));
        } else {
            $dob = $directory->getDob();
        }
        $directory->setDob($dob);
        $directory->setLandline($form->has('landLine') ? $form->get('landLine')->getData() : $directory->getLandline());
        $directory->setCompanyName($form->get('companyName')->getData() ? $form->get('companyName')->getData() : $directory->getCompanyName());
        $directory->setCountryCode($form->has('countryCode') ? $form->get('countryCode')->getData() : $directory->getCountryCode());

        $em = $this->doctrine->getManager();
        $property = $directory->getProperty();
        if ($form->has('janitorInvite') && true === $form->get('janitorInvite')->getData()) {
            if ($directory->getProperty() instanceof Property && $property->getJanitor() instanceof UserIdentity &&
                $property->getJanitor() !== $directory->getUser()) {
                throw new InvalidArgumentException('janitorAlreadyPresent');
            }
        }
        $janitorInvite = ($form->has('janitorInvite') && true === $form->get('janitorInvite')->getData());
        $this->propertyService->addAdministratorInvitation($directory->getUser(), $directory->getInvitor(), $property, $janitorInvite);
        if ($janitorInvite && $form->has('sendInvite') && !$form->get('sendInvite')->getData()) {
            $property->setJanitor($directory->getUser());
        }
        $em->flush();
    }

    /**
     * @param Directory $directory
     * @return void
     * @throws \Exception
     */
    public function checkUserStatus(Directory $directory): void
    {
        $em = $this->doctrine->getManager();
        $properties = $em->getRepository(Property::class)->findProperties(['user' => $directory->getInvitor()]);
        if (!$this->propertyService->userAllocationInProperties($properties, $directory->getUser())) {
            throw new \Exception('userAllocationExists');
        }
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
        $this->containerUtility->sendEmailConfirmation($user, 'ResendIndividualRegistration', $locale, 'Invitation', 'individual', $param);
    }

    /**
     *
     * @param UserIdentity $user
     * @return array
     */
    public function getPropertyAllocationDetails(UserIdentity $user): array
    {
        $em = $this->doctrine->getManager();
        $adminAllocations = $em->getRepository(Property::class)->findBy(['administrator' => $user, 'deleted' => false]);
        $janitorAllocations = $em->getRepository(Property::class)->findBy(['janitor' => $user, 'deleted' => false]);
        $list = [];
        $i = 0;
        foreach ($adminAllocations as $adminAllocation) {
            $role = $em->getRepository(Role::class)->findOneBy(['roleKey' => $this->params->get('user_roles')['property_admin'], 'deleted' => false]);
            $list[$i]['name'] = $adminAllocation->getAddress();
            $list[$i]['propertyId'] = $adminAllocation->getPublicId();
            $list[$i]['role'] = $role->getName();
            $list[$i]['startDate'] = $adminAllocation->getPlanStartDate();
            $list[$i]['endDate'] = $adminAllocation->getPlanEndDate();
            $list[$i]['active'] = $adminAllocation->getActive();
            $i++;
        }
        foreach ($janitorAllocations as $janitorAllocation) {
            $role = $em->getRepository(Role::class)->findOneBy(['roleKey' => $this->params->get('user_roles')['janitor'], 'deleted' => false]);
            $list[$i]['name'] = $janitorAllocation->getAddress();
            $list[$i]['role'] = $role->getName();
            $list[$i]['propertyId'] = $janitorAllocation->getPublicId();
            $list[$i]['startDate'] = $janitorAllocation->getPlanStartDate();
            $list[$i]['endDate'] = $janitorAllocation->getPlanEndDate();
            $list[$i]['active'] = $janitorAllocation->getActive();
            $i++;
        }

        return $list;
    }

    /**
     *
     * @param string $name
     * @return string
     */
    public function getDynamicEmail(string $name): string
    {
        $em = $this->doctrine->getManager();
        $property = $this->generateDynamicEmail($name);
        if (!$em->getRepository(User::class)->findOneByProperty([$property])) {
            return $property;
        }

        return $this->getDynamicEmail($name);
    }


    /**
     *
     * @param string $property
     * @return string
     */
    public function generateDynamicEmail(string $property): string
    {
        return $property . uniqid(md5(time()), false) . '@' . $this->params->get('email_domain');
    }

    /**
     * Get people directory
     *
     * @param string|null $property
     * @param string|null $parameter
     * @param string|null $locale
     * @return array
     */
    public function getPeopleDirectory(?string $property, ?string $parameter = null, ?string $locale = 'en'): array
    {
        $data = array();
        $em = $this->doctrine->getManager();
        $property = $em->getRepository(Property::class)->findOneBy(['publicId' => $property]);
        if ($property instanceof Property) {
            $peopleList = $em->getRepository(PropertyUser::class)->getPeopleList($property->getIdentifier(), $parameter);
            $data = $this->getFormattedPeopleDirectory($peopleList, $property->getIdentifier());
        }
        return $data;
    }

    /**
     * searchPeople
     *
     * @param string|null $property
     * @param string|null $parameter
     * @param string|null $locale
     * @return array
     */
    public function searchPeople(?string $property, ?string $parameter = null, ?string $locale = 'en'): array
    {
        return $this->getPeopleDirectory($property, $parameter);
    }

    /**
     * getFormattedPeopleDirectory
     *
     * @param array $people
     * @param int $property
     * @return array
     */
    private function getFormattedPeopleDirectory(array $people, int $property): array
    {
        $data = [];
        if (!empty($people)) {
            foreach ($people as $key => $directory) {
                $data[$key]['publicId'] = $directory['publicId'];
                $data[$key]['propertyUserIdentifier'] = $directory['propertyUserIdentifier'];
                $data[$key]['directoryId'] = $directory['directoryId'];
                $data[$key]['identifier'] = $directory['identifier'];
                $data[$key]['name'] = $directory['companyName'];
                $data[$key]['firstName'] = $directory['firstName'] ? $directory['firstName'] : $directory['directoryFirstName'];
                $data[$key]['lastName'] = $directory['lastName'] ? $directory['lastName'] : $directory['directoryLastName'];
                $data[$key]['directoryFirstName'] = $directory['directoryFirstName'];
                $data[$key]['directoryLastName'] = $directory['directoryLastName'];
                $data[$key]['directoryStreet'] = $directory['directoryStreet'];
                $data[$key]['directoryStreetNumber'] = $directory['directoryStreetNumber'];
                $data[$key]['directoryCity'] = $directory['directoryCity'];
                $data[$key]['directoryCountry'] = $directory['directoryCountry'];
                $data[$key]['directoryZipCode'] = $directory['directoryZipCode'];
                $data[$key]['companyName'] = $directory['companyName'];
                $data[$key]['name'] = $directory['companyName'] != null ? $directory['companyName'] : $directory['directoryFirstName'] . ' ' . $directory['directoryLastName'];
                $data[$key]['email'] = $directory['property'];
                $data[$key]['phone'] = $directory['directoryPhone'];
                $data[$key]['isJanitor'] = $directory['isJanitor'];
                $data[$key]['isRegisteredUser'] = !is_null($directory['firstLogin']);
                $objectAllocations = $this->doctrine->getRepository(PropertyUser::class)->getUserObjectAllocationsOfProperty($property, (int)$directory['user']);
                $data[$key]['objectAllocations'] = $objectAllocations;
            }
        }
        return $data;
    }

    /**
     * checkAllocationStatus
     *
     * @param Directory $directory
     * @return void
     */
    public function checkAllocationStatus(Directory $directory): void
    {
        $allocation = $this->doctrine->getRepository(PropertyUser::class)->findOneBy([
            'user' => $directory->getUser(),
            'property' => $directory->getProperty(),
            'deleted' => false,
            'isActive' => true
        ]);
        if ($allocation instanceof PropertyUser && $allocation->getContract() instanceof ObjectContracts) {
            throw new CustomUserMessageAccountStatusException('userAllocationExists');
        }
    }

    /**
     * checkUserExistsInDirectory
     *
     * @param string $property
     * @param UserIdentity $user
     * @return void
     * @throws EntityNotFoundException
     */
    public function checkUserExistsInDirectory(UserIdentity $user, ?string $property = null): void
    {
        $property = $this->doctrine->getRepository(Property::class)->findOneBy(['publicId' => $property]);
        if (!$property instanceof Property) {
            throw new EntityNotFoundException('invalidProperty');
        }
        $propertyUser = $this->doctrine->getRepository(PropertyUser::class)->findOneBy([
            'property' => $property->getIdentifier(),
            'user' => $user->getIdentifier(),
            'deleted' => false,
            'isActive' => true
        ]);
        $directory = $this->doctrine->getRepository(Directory::class)->findOneBy([
            'property' => $property->getIdentifier(),
            'user' => $user->getIdentifier(),
            'deleted' => false
        ]);
        if ($propertyUser instanceof PropertyUser && $directory instanceof Directory) {
            throw new CustomUserMessageAccountStatusException('userAlreadyAvailableInDirectory');
        }
    }
}
