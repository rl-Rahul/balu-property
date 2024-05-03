<?php

/**
 * This file is part of the Balu 2.0 Project package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Entity\User;
use App\Entity\UserPropertyPool;
use App\Entity\Directory;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use App\Entity\UserIdentity;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Form\Form;
use App\Utils\Constants;
use Symfony\Component\Process\Exception\InvalidArgumentException;

/**
 * ValidationUtility
 *
 * Utility class to handle validation functions
 *
 * @package         Wedoit
 * @subpackage      App
 */
class ValidationUtility
{
    /**
     * @var GeneralUtility $generalUtility
     */
    private GeneralUtility $generalUtility;

    /**
     * @var ValidatorInterface $validator
     */
    private ValidatorInterface $validator;

    /**
     * @var ManagerRegistry $doctrine
     */
    private ManagerRegistry $doctrine;

    /**
     * @var UserPasswordHasherInterface $passwordHasher
     */
    private UserPasswordHasherInterface $passwordHasher;

    /**
     * @var ValidationConstraints $oConstraintManager
     */
    private ValidationConstraints $oConstraintManager;

    /**
     * Constructor
     *
     * @param GeneralUtility $generalUtility
     * @param ValidatorInterface $validator
     * @param ManagerRegistry $doctrine
     * @param UserPasswordHasherInterface $passwordHasher
     * @param ValidationConstraints $oConstraintManager
     */
    public function __construct(GeneralUtility $generalUtility, ValidatorInterface $validator, ManagerRegistry $doctrine, UserPasswordHasherInterface $passwordHasher, ValidationConstraints $oConstraintManager)
    {
        $this->generalUtility = $generalUtility;
        $this->validator = $validator;
        $this->doctrine = $doctrine;
        $this->passwordHasher = $passwordHasher;
        $this->oConstraintManager = $oConstraintManager;
    }

    /**
     * Validates the user status
     *
     * @param UserIdentity $oUser
     * @param $option
     * @return void
     */
    public function validateUserStatus(UserIdentity $oUser, $option): void
    {
        if (!$oUser instanceof UserIdentity) {
            throw new UserNotFoundException('userdNotFound', 400);
        }
        if (true === $oUser->getIsBlocked()) {
            throw new AccessDeniedException('userBlocked');
        }
        if (true === $oUser->getDeleted()) {
            throw new CustomUserMessageAuthenticationException('userDeleted', [], 401);
        }
        if ((isset($option) && $option === 'forgotPassword') && false === $oUser->getEnabled()) {
            throw new CustomUserMessageAccountStatusException('userNotEnabled', [], 401);
        }
        if ((isset($option) && $option === 'register') && true === $oUser->getEnabled()) {
            throw new CustomUserMessageAccountStatusException('userAlreadyRegistered', [], 401);
        }
    }

    /**
     * Password checker to check the if the password matches all the criterion
     *
     * @param Form $form
     * @param User|null $user
     * @return boolean
     */
    public function isValidPassword(Form $form, ?User $user = null): bool
    {
        $password = $form->has('newPassword') ? $form->get('newPassword')->getData() : $form->get('password')->getData();
        if ($password !== $form->get('confirmPassword')->getData()) {
            throw new InvalidPasswordException('passwordMismatch');
        }
        if (strlen($password) < 8) {
            throw new InvalidPasswordException('passwordTooShort');
        }
        if ($user instanceof User) {
            $oldPassword = $form->has('currentPassword') ? $form->get('currentPassword')->getData() : $form->get('confirmPassword')->getData();
            $passwordToCheckHash = $this->passwordHasher->hashPassword($user, $oldPassword);
            if (password_verify($form->get('confirmPassword')->getData(), $passwordToCheckHash)) {
                throw new InvalidPasswordException('oldNewPasswordSame');
            }
        }
        if (false == preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}/i', $password)) {
            throw new InvalidPasswordException('wrongPasswordCriterion');
        }
        return true;
    }

    /**
     * function to find missing Mandatory Fields
     *
     * @param ConstraintViolationList $errors
     * @return array
     * @throws \Exception
     */
    public function missingMandatoryFields(ConstraintViolationList $errors): array
    {
        $errArr = [];
        foreach ($errors->getIterator() as $error) {
            $errArr[$error->getPropertyPath()] = $error->getMessage();
        }
        return $errArr;
    }

    /**
     * Function validate to object using the validator service
     *
     * @param object $object
     * @return object
     */
    public function validateConstraints(object $object): object
    {
        return $this->validator->validate($object);
    }

    /**
     * Common method to build error message.
     *
     * @param \ArrayIterator $oViolations
     *
     * @return array
     */
    private function buildErrorData(\ArrayIterator $oViolations): array
    {
        $aViolations = [];
        foreach ($oViolations as $sKey => $oViolation) {
            $aViolations[str_replace(['[', ']'], '', $oViolation->getPropertyPath())][] = $oViolation->getMessage();
        }

        return $aViolations;
    }

    /**
     * @param string $function
     * @param array $aData
     *
     * @return array
     * @throws \Exception
     */
    public function validateData(string $function, array $aData): array
    {
        $aViolations = [];
        $oViolations = Validation::createValidator()->validate($aData, $this->oConstraintManager->get(ucfirst($function)))->getIterator();
        if ($oViolations) {
            $aViolations = $this->buildErrorData($oViolations);
        }
        if (isset($aData['locales']) && is_array($aData['locales'])) {
            $aLocaleViolations = $this->validateLocaleData($aData['locales']);
            if (count($aLocaleViolations) > 0) {
                $aViolations['locales'] = $aLocaleViolations;
            }
        }
        return $aViolations;
    }

    /**
     * @param array $aTranslations
     *
     * @return array
     * @throws \Exception
     */
    private function validateLocaleData(array $aTranslations): array
    {
        $aLocaleViolations = [];
        foreach ($aTranslations as $sKey => $aLocale) {
            $aLocaleViolations[$sKey] = Validation::createValidator()->validate($aLocale, $this->oConstraintManager->get('locale'))->getIterator();
        }

        $aViolations = [];
        if ($aLocaleViolations) {
            $aViolations = $this->buildNestedErrorData($aLocaleViolations);
        }

        return $aViolations;
    }

    /**
     * @param array $aNestedViolations
     *
     * @return array
     */
    private function buildNestedErrorData(array $aNestedViolations): array
    {
        $aViolations = [];
        foreach ($aNestedViolations as $sKey => $oViolations) {
            if (count($oViolations)) {
                $aViolations[$sKey] = $this->buildErrorData($oViolations, $sKey);
            }
        }

        return $aViolations;
    }

    /**
     * Function to get all errors in a nested form
     *
     * @param FormErrorIterator $errors
     * @return array
     */
    public function getNestedFormErrors(FormErrorIterator $errors): array
    {
        $errArr = [];
        foreach ($errors as $formError) {
            $errArr[$formError->getOrigin()->getName()] = $formError->getMessage();
        }
        return $errArr;
    }

    /**
     * Function to check if uuid is valid or not
     *
     * @param string $object
     * @param string $uuid
     * @return bool
     */
    public function checkIfUuidValid(string $object, string $uuid): bool
    {
        $em = $this->doctrine->getManager();
        $object = $em->getRepository("App\\Entity\\" . ucfirst($object))->findOneBy(['publicId' => $uuid]);

        return ($object) ? true : false;
    }

    /**
     * Function to validate text Input. Checks for contact info in input.
     *
     * @param string|null $string $string
     * @return bool
     */
    public function isValidText(?string $string): bool
    {
        if (null === $string || trim($string) === '') {
            return true;
        }
        // check email
        $regex_email = '/[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})/';
        // check phone
        $regex_phone = '/\b[0-9]{3}\s*[-]?\s*[0-9]{3}\s*[-]?\s*[0-9]{4}\b/';

        if (preg_match($regex_email, $string) || preg_match($regex_phone, $string)) {
            return false;
        }

        return true;
    }

    /**
     * Function to validate search text Input. Checks for empty content.
     *
     * @param string $searchText
     * @return bool
     */
    public function validateSearchText(string $searchText): bool
    {
        if (null === $searchText || trim($searchText) === '') {
            return false;
        }
        return true;
    }

    /**
     * Function to check if an email is already exists or not
     *
     * @param string|null $email
     * @param bool|null $isCurrentUser
     * @return bool
     */
    public function checkEmailAlreadyExists(?string $email, ?bool $isCurrentUser = false): bool
    {
        $em = $this->doctrine->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['property' => $email]);
        $emailInPropertyPool = $em->getRepository(UserPropertyPool::class)->findBy(['property' => $email]);
        if (!$isCurrentUser && ($user instanceof User || !empty($emailInPropertyPool))) {
            return true;
        }
        return false;
    }

    /**
     * Function to generate random password
     *
     * @param int $length
     * @return string
     */
    public function generatePassword(int $length): string
    {
        $chars = "abcdefghjkmnopqrstuvwxyzABCDEFGHJKMNOPQRSTUVWXYZ0123456789";
        return substr(str_shuffle($chars), 0, $length);
    }

    /**
     * Function to validate scope of favourite user
     *
     * @param string $user
     * @param string $role
     * @return UserIdentity $favUser
     * @throws InvalidArgumentException
     */
    public function validateFavouriteUserAndRole(string $user, string $role): UserIdentity
    {
        $em = $this->doctrine->getManager();
        //validate if the role given have favourite option
        if (!in_array($role, Constants::FAVOURITES_ROLES)) {
            throw new \InvalidArgumentException('invalidRole');
        }
        //validate if user and given role is matching
        if ($role == Constants::FAVOURITES_ROLES[0]) {
            $directory = $em->getRepository(Directory::Class)->getUserWithRole($user, $role);
            if (!$directory instanceof Directory) {
                throw new \InvalidArgumentException('invalidUser');
            }
            $favUser = $directory->getUser();
        } else {
            $favUser = $em->getRepository(UserIdentity::Class)->getUserWithRole($user, $role);
            if (!$favUser instanceof UserIdentity) {
                throw new \InvalidArgumentException('invalidUser');
            }
        }
        return $favUser;
    }
}