<?php

/**
 * This file is part of the Balu 2.0 Project package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;


use App\Entity\Address;
use App\Entity\Category;
use App\Entity\Damage;
use App\Entity\Document;
use App\Entity\Role;
use App\Entity\TemporaryUpload;
use App\Entity\User;
use App\Entity\UserIdentity;
use App\Entity\UserPropertyPool;
use App\Entity\UserSubscription;
use App\Utils\Constants;
use App\Utils\ContainerUtility;
use App\Utils\ValidationUtility;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\CompanySubscriptionPlan;

/**
 * RegistrationService
 *
 * Service class to handle registration features
 *
 * @package         PITS
 * @subpackage      App
 * @author          Rahul <rahul.rl@pitsolutions.com>
 */
class RegistrationService extends BaseService
{
    /**
     * @var ContainerUtility $containerUtility
     */
    private ContainerUtility $containerUtility;

    /**
     * @var ManagerRegistry $doctrine
     */
    private ManagerRegistry $doctrine;

    /**
     * @var ValidationUtility $validationUtility
     */
    private ValidationUtility $validationUtility;

    /**
     * @var SecurityService $securityService
     */
    private SecurityService $securityService;

    /**
     * @var ParameterBagInterface $params
     */
    private ParameterBagInterface $params;

    /**
     * @var DMSService $dmsService
     */
    private DMSService $dmsService;

    /**
     * Constructor
     * @param ManagerRegistry $doctrine
     * @param ContainerUtility $containerUtility
     * @param ValidationUtility $validationUtility
     * @param SecurityService $securityService
     * @param ParameterBagInterface $params
     * @param DMSService $dmsService
     */
    public function __construct(ManagerRegistry $doctrine, ContainerUtility $containerUtility,
                                ValidationUtility $validationUtility, SecurityService $securityService,
                                ParameterBagInterface $params, DMSService $dmsService)
    {
        $this->doctrine = $doctrine;
        $this->containerUtility = $containerUtility;
        $this->validationUtility = $validationUtility;
        $this->securityService = $securityService;
        $this->params = $params;
        $this->dmsService = $dmsService;
    }

    /**
     * registerUser
     * @param Form $form
     * @param UserIdentity $userIdentity
     * @param UserPasswordHasherInterface $passwordHasher
     * @param bool $selfRegister
     * @param bool|null $isSystemGeneratedEmail
     * @return array|null
     * @throws InvalidPasswordException
     * @throws \Exception
     */
    public function registerUser(Form $form, UserIdentity $userIdentity, UserPasswordHasherInterface $passwordHasher, bool $selfRegister = true, ?bool $isSystemGeneratedEmail = false): ?array
    {
        if ($form->has('password') && $form->has('confirmPassword') &&
            !$this->validationUtility->isValidPassword($form)) {
            throw new InvalidPasswordException('invalidPassword', 400);
        }
        $em = $this->doctrine->getManager();
        $user = new User();
        $email = $form->get('email')->getData();
        $user->setProperty($email);
        $password = $form->has('password') ?
            $form->get('password')->getData() :
            $this->validationUtility->generatePassword(8);
        $param['password'] = $password;
        $param['email'] = $email;
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $em->persist($user);
        $em->flush();
        $userIdentity->setUser($user);
        $enabled = $selfRegister ? false : true;
        $userIdentity->setEnabled($enabled);
        $userIdentity->setIsAppUseEnabled(true);
        if ($this->securityService->getUser()) {
            $userIdentity->setCreatedBy($this->securityService->getUser());
        }
        $em->persist($userIdentity);
        $em->flush();
        if (!$isSystemGeneratedEmail) {
            $propertyPool = new UserPropertyPool();
            $propertyPool->setUser($user);
            $propertyPool->setProperty($email);
            $propertyPool->setType('email');
            $em->persist($propertyPool);
            $em->flush();
        }
        if ($form->has('role') && $form->get('role')->getData() === Constants::COMPANY_ROLE) {
            $this->companyDetails($form, $userIdentity);
        }
        $this->userAddressData($form, $userIdentity, new Address());
        if ($form->has('role') && null !== $form->get('role')->getData()) {
            $this->userRoles($form->get('role')->getData(), $userIdentity);
        }
        return $param;
    }

    /**
     * addUserAddress
     *
     * @param Form $form
     * @param UserIdentity $userIdentity
     * @param Address $userAddress
     * @param bool $isEdit
     * @throws \Exception
     */
    public function userAddressData(Form $form, UserIdentity $userIdentity, Address $userAddress, bool $isEdit = false): void
    {
        $toInsert['city'] = $form->has('city') ? $form->get('city')->getData() : null;
        $toInsert['street'] = $form->has('street') ? $form->get('street')->getData() : null;
        $toInsert['streetNumber'] = $form->has('streetNumber') ? $form->get('streetNumber')->getData() : null;
        $toInsert['zipCode'] = $form->has('zipCode') ? $form->get('zipCode')->getData() : null;
        $toInsert['phone'] = $form->get('phone')->getData();
        $toInsert['country'] = $form->has('country') ? $form->get('country')->getData() : null;
        $toInsert['countryCode'] = $form->has('countryCode') ? $form->get('countryCode')->getData() : null;
        $toInsert['user'] = $userIdentity;
        $toInsert['landLine'] = $form->has('landLine') ? $form->get('landLine')->getData() : null;
        if ($isEdit) {
            $toInsert['updatedAt'] = new \DateTime('now');
        }
        $toInsert['latitude'] = $form->has('latitude') ? $form->get('latitude')->getData() : null;
        $toInsert['longitude'] = $form->has('longitude') ? $form->get('longitude')->getData() : null;
        $this->containerUtility->convertRequestKeysToSetters($toInsert, $userAddress);
    }

    /**
     * @param Form $form
     * @param UserIdentity $userIdentity
     * @param bool $isEdit
     * @param bool $isAdminEdit
     * @throws \PhpZip\Exception\ZipException
     */
    public function companyDetails(Form $form, UserIdentity $userIdentity, bool $isEdit = false, bool $isAdminEdit = false): void
    {
        $em = $this->doctrine->getManager();
        $categories = $form->get('category')->getData();
        if (!empty($categories)) {
            foreach ($userIdentity->getCategories() as $cat) {
                if ($cat instanceof Category) {
                    $userIdentity->removeCategory($cat);
                }
            }
            $em->flush();

            foreach ($categories as $publicId) {
                $category = $em->getRepository(Category::class)->findOneBy(['publicId' => $publicId]);
                if ($category instanceof Category) {
                    $userIdentity->addCategory($category);
                }
            }
            $em->flush();
        }
        if (!$isEdit) {
//            $userIdentity->setEnabled(true);
            $userSubscription = new UserSubscription();
            $userSubscription->setUser($userIdentity);
            $em->persist($userSubscription);

            $em->flush();
        }
        if (!$isAdminEdit) {
            $curDate = new \DateTime('now');
            $freeSubscriptionPlan = $em->getRepository(CompanySubscriptionPlan::class)->findOneBy(['initialPlan' => 1, 'active' => 1]);
            $companyExpiryLimit = $freeSubscriptionPlan->getPeriod();
            $companyExpiryDate = $curDate->modify('+' . $companyExpiryLimit . 'day');
            $userIdentity->setPlanEndDate($companyExpiryDate);
            $userIdentity->setIsFreePlanSubscribed(true);
            $userIdentity->setCompanySubscriptionPlan($freeSubscriptionPlan);
            $userIdentity->setIsExpired(false);
            $em->flush();
            if ($form->has('document') && $form->get('document')->getData() != '') {
                $roleKey = $this->camelCaseConverter($form->get('role')->getData());
                $this->saveCompanyLogo($form->get('document')->getData(), $userIdentity, $roleKey);
            } else {
                $this->dmsService->removeCompanyLogo();
            }
        }
    }

    /**
     * @param string $role
     * @param UserIdentity $userIdentity
     */
    public function userRoles(string $role, UserIdentity $userIdentity): void
    {
        $em = $this->doctrine->getManager();
        $roleKey = $this->camelCaseConverter($role);
        $role = $em->getRepository(Role::class)->findOneBy(['roleKey' => $roleKey, 'deleted' => false]);
        if ($role instanceof Role) {
            $userIdentity->addRole($role);
        }
        $em->flush();
    }

    /**
     * saveCompanyLogo
     *
     * @param string $document
     * @param UserIdentity $company
     * @param string $roleKey
     * @throws \PhpZip\Exception\ZipException
     */
    private function saveCompanyLogo(string $document, UserIdentity $company, string $roleKey): void
    {
        $em = $this->doctrine->getManager();
        $tempDocument = $em->getRepository(TemporaryUpload::class)->findOneBy(['publicId' => $document]);
        $document = $em->getRepository(Document::class)->findOneBy(['publicId' => $document]);
        $role = $em->getRepository(Role::class)->findOneBy(['roleKey' => $roleKey]);
        if ($tempDocument instanceof TemporaryUpload && !$document instanceof Document) {
            $this->dmsService->persistCompanyLogo($tempDocument, $company, $role);
        }
    }

    /**
     * createDisabledCompany
     *
     * function to save and return disabled company
     *
     * @param string $email
     * @param UserPasswordHasherInterface $passwordHasher
     * @param User|null $user
     * @param UserIdentity|null $userIdentity
     * @return UserIdentity
     */
    public function createDisabledCompany(string $email, UserPasswordHasherInterface $passwordHasher,
                                          ?UserIdentity $userIdentity = null, ?User $user = null): UserIdentity
    {
        $em = $this->doctrine->getManager();
        if (!$user instanceof User) {
            $user = new User();
            $user->setProperty($email);
            $em->persist($user);
        }
        $password = $this->validationUtility->generatePassword(8);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $em->flush();
        $role = Constants::COMPANY_ROLE;
        if (!$userIdentity instanceof UserIdentity) {
            $userIdentity = new UserIdentity();
            $role = Constants::GUEST_ROLE;
        }
        $userIdentity->setUser($user);
        $userIdentity->setEnabled(false);
        if ($this->securityService->getUser() instanceof UserIdentity) {
            $userIdentity->setCreatedBy($this->securityService->getUser());
        }
        $em->persist($userIdentity);
        $em->flush();
        $this->userRoles($role, $userIdentity);

        return $userIdentity;
    }

    /**
     * guestUserRegistration
     *
     * function to save and return guest users
     *
     * @param Form $form
     * @param UserIdentity $userIdentity
     * @param UserPasswordHasherInterface $passwordHasher
     * @param bool $update
     * @return UserIdentity
     * @throws \Exception
     */
    public function guestUserRegistration(Form $form, UserIdentity $userIdentity,
                                          UserPasswordHasherInterface $passwordHasher, bool $update = false): UserIdentity
    {
        $em = $this->doctrine->getManager();
        $email = $form->get('email')->getData();
        $user = null;
        $address = new Address();
        if ($update) {
            $user = $userIdentity->getUser();
            $address = $em->getRepository(Address::class)->findOneBy(['user' => $userIdentity->getIdentifier()]);
        }
        $userIdentity = $this->createDisabledCompany($email, $passwordHasher, $userIdentity, $user);
        $userIdentity->setIsAppUseEnabled(false);
        $userIdentity->setIsGuestUser(true);
        $userIdentity->setLanguage('en');
        $userIdentity->getUser()->setRoles([Constants::ROLE_GUEST]);
        $this->userAddressData($form, $userIdentity, $address);
        $em->flush();
        if (!$update) {
            $propertyPool = new UserPropertyPool();
            $propertyPool->setUser($userIdentity->getUser());
            $propertyPool->setProperty($email);
            $propertyPool->setType('email');
            $em->persist($propertyPool);
            $em->flush();
        }
        return $userIdentity;
    }

    /**
     * checkOtpExists
     *
     * function to check and save OTP for Guest user verification
     *
     * @param UserIdentity $userIdentity
     * @return UserIdentity
     */
    public function checkAndSaveOtp(UserIdentity $userIdentity): UserIdentity
    {
        $validationOTP = $this->generateUniqueRandomNumber($userIdentity->getUser()->getProperty());
        $userIdentity->setAuthCode($validationOTP);

        return $userIdentity;
    }

    /**
     * generateUniqueOtp
     *
     * function to get random otp
     *
     * @param string $email
     * @param int $length
     * @return int
     */
    private function generateUniqueRandomNumber(string $email, int $length = 6): int
    {
        $em = $this->doctrine->getManager();
        $maxAttempts = 1000; // Set a reasonable limit on the number of attempts
        for ($i = 0; $i < $maxAttempts; $i++) {
            $randomNumber = str_pad(mt_rand(0, 999999), $length, '0', STR_PAD_LEFT); // Generate a 6-character random number
            // Check if the number already exists in the database
            $existingUser = $em->getRepository(UserIdentity::class)->findOneByAuthCode(['authCode' => $randomNumber, 'email' => $email]);
            if (!$existingUser instanceof UserIdentity) {
                return $randomNumber; // Unique number found, return it
            }
        }
        throw new \RuntimeException('Unable to generate a unique random number.');
    }

    /**
     * updateUser
     *
     * @param Form $form
     * @param UserIdentity $userIdentity
     * @param UserPasswordHasherInterface $passwordHasher
     * @param CompanyService $companyService
     * @param DamageService $damageService
     * @param bool $isRegister
     * @return array|null
     * @throws \PhpZip\Exception\ZipException
     * @throws \Exception
     */
    public function updateUser(Form $form, UserIdentity $userIdentity, UserPasswordHasherInterface $passwordHasher,
                               CompanyService $companyService, DamageService $damageService, bool $isRegister = false): ?array
    {
        if ($form->has('password') && $form->has('confirmPassword') &&
            !$this->validationUtility->isValidPassword($form)) {
            throw new InvalidPasswordException('invalidPassword', 400);
        }
        $em = $this->doctrine->getManager();
        $email = $form->get('email')->getData();
        $password = $form->has('password') ?
            $form->get('password')->getData() :
            $this->validationUtility->generatePassword(8);
        if ($isRegister) {
            $user = $this->handleUserRegistration($email, $password, $em, $passwordHasher);
        } else {
            $user = $userIdentity->getUser();
            $user->setPassword($passwordHasher->hashPassword($user, $password));
        }
        $param['password'] = $password;
        $param['email'] = $email;
        $userIdentity->setUser($user);
        $userIdentity->setEnabled(false);
        $userIdentity->setIsAppUseEnabled(true);
        $userIdentity->setIsGuestUser(false);
        if ($this->securityService->getUser() instanceof UserIdentity) {
            $userIdentity->setCreatedBy($this->securityService->getUser());
        }
        $em->flush();
        if ($form->has('role') && $form->get('role')->getData() === Constants::COMPANY_ROLE) {
            $this->companyDetails($form, $userIdentity, true);
        }
        if ($form->has('damage') && $form->get('damage')->getData() !== "") {
            $damage = $em->getRepository(Damage::class)->findOneBy(['publicId' => $form->get('damage')->getData()]);
            if ($damage instanceof Damage) {
                $params['company'] = [$userIdentity->getPublicId()];
                $statusKey = $damage->getCompanyAssignedByRole() instanceof Role ?
                    strtoupper($damage->getCompanyAssignedByRole()->getRoleKey()) :
                    strtoupper($damage->getCreatedByRole()->getRoleKey());
                $params['status'] = $statusKey . '_SEND_TO_COMPANY_WITHOUT_OFFER';
                $params['damage'] = $form->get('damage')->getData();
                $companyService->saveDamageRequest($params, $passwordHasher, Constants::COMPANY_ROLE);
                $damageService->logDamage($userIdentity, $damage, null, null, null, $params['company']);
            }
        }
        $address = $em->getRepository(Address::class)->findOneBy(['user' => $userIdentity->getIdentifier()]);
        $address = $address instanceof Address ? $address : new Address();
        $this->userAddressData($form, $userIdentity, $address);
        if ($form->has('role') && null !== $form->get('role')->getData()) {
            $this->userRoles($form->get('role')->getData(), $userIdentity);
        }
        return $param;
    }

    /**
     * handleUserRegistration
     *
     * @param string $email
     * @param string $password
     * @param EntityManager $em
     * @param UserPasswordHasherInterface $hasher
     * @return User|null
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function handleUserRegistration(string $email, string $password, EntityManager $em, UserPasswordHasherInterface $hasher): ?User
    {
        $user = new User();
        $user->setProperty($email);
        $user->setPassword($hasher->hashPassword($user, $password));
        $em->persist($user);
        $em->flush();
        $propertyPool = new UserPropertyPool();
        $propertyPool->setUser($user);
        $propertyPool->setProperty($email);
        $propertyPool->setType('email');
        $em->persist($propertyPool);
        $em->flush();

        return $user;
    }
}