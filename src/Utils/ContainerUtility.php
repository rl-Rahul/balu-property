<?php

/**
 * This file is part of the Balu 2.0 Project package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Entity\Damage;
use App\Entity\User;
use App\Entity\UserIdentity;
use App\Entity\DamageStatus;
use App\Helpers\EmailServiceHelper;
use App\Helpers\TemplateHelper;
use App\Service\CompanyService;
use App\Service\DamageService;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use App\Service\PushNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Role;

/**
 * ContainerUtility
 *
 * Utility class to handle container included functions
 *
 * @package         Balu 2.0
 * @subpackage      App
 * @author          Rahul <rahul.rl@pitsolutions.com>
 */
class ContainerUtility
{
    /**
     * @var ManagerRegistry $doctrine
     */
    private ManagerRegistry $doctrine;

    /**
     * @var ValidationUtility $validator
     */
    private ValidationUtility $validator;

    /**
     * @var ParameterBagInterface $parameterBag
     */
    private ParameterBagInterface $parameterBag;

    /**
     * @var TranslatorInterface $translator
     */
    private TranslatorInterface $translator;

    /**
     * @var EmailServiceHelper $emailServiceHelper
     */
    private EmailServiceHelper $emailServiceHelper;

    /**
     * @var TemplateHelper $templateHelper
     */
    private TemplateHelper $templateHelper;

    /**
     * @var SecurityService $securityService
     */
    private SecurityService $securityService;

    /**
     * @var PushNotificationService $pushNotificationService
     */
    private PushNotificationService $pushNotificationService;

    /**
     * Constructor
     *
     * @param ValidationUtility $validator
     * @param ManagerRegistry $doctrine
     * @param ParameterBagInterface $parameterBag
     * @param TranslatorInterface $translator
     * @param EmailServiceHelper $emailServiceHelper
     * @param TemplateHelper $templateHelper
     * @param PushNotificationService $pushNotificationService
     * @param SecurityService $securityService
     */
    public function __construct(ValidationUtility $validator, ManagerRegistry $doctrine,
                                ParameterBagInterface $parameterBag, TranslatorInterface $translator,
                                EmailServiceHelper $emailServiceHelper, TemplateHelper $templateHelper,
                                PushNotificationService $pushNotificationService, SecurityService $securityService
    )
    {
        $this->validator = $validator;
        $this->doctrine = $doctrine;
        $this->parameterBag = $parameterBag;
        $this->translator = $translator;
        $this->emailServiceHelper = $emailServiceHelper;
        $this->templateHelper = $templateHelper;
        $this->pushNotificationService = $pushNotificationService;
        $this->securityService = $securityService;
    }

    /**
     * Function to convert request array keys to Setters
     *
     * @param array $request
     * @param $object
     * @return mixed
     * @throws \Exception
     */
    public function convertRequestKeysToSetters(array $request, $object = null)
    {
        $setters = array();
        $className = (!is_null($object) && is_object($object)) ? get_class($object) : null;
        if (is_array($request)) {
            foreach ($request as $key => $value) {
                if (strpos($key, '_') !== false) {
                    $setter = 'set' . str_replace('_', '', ucwords($key, "_"));
                } else {
                    $setter = 'set' . ucfirst($key);
                }
                $setters[$setter] = $value;
                if (!is_null($object) && is_object($object) && $object instanceof $className) {
                    $object->$setter($value);
                }
            }
        }
        if (!is_null($object) && is_object($object) && $object instanceof $className) {
            $errors = $this->validator->validateConstraints($object);
            if (count($errors) > 0) {
                $errorsString = (string)$errors;
                throw new \Exception($errorsString);
            }
            $em = $this->doctrine->getManager();
            $em->persist($object);
            $em->flush();
            return $object;
        }
        return $setters;
    }

    /**
     * Function to get the url prefix
     *
     * @param string $encryptString
     * @param string $userLanguage
     * @param bool $isForgotPassword
     * @return string
     */
    public function getUrl(string $encryptString, string $userLanguage, bool $isForgotPassword): string
    {
        $domain = $this->parameterBag->get('FE_DOMAIN');
        $function = $isForgotPassword ? 'forgot_password_url' : 'registration_url';
        $uri = sprintf($this->parameterBag->get($function), $encryptString, $userLanguage);
        return $domain . $uri;
    }

    /**
     * Function to get the url prefix
     *
     * @param string $encryptString
     * @param string $userLanguage
     * @return string
     */
    public function getMoreContents(string $encryptString, string $userLanguage): string
    {
        $domain = $this->parameterBag->get('FE_DOMAIN');
        $uri = sprintf($this->parameterBag->get('more_url'), $encryptString, $userLanguage);
        return $domain . $uri;
    }

    /**
     * encryption of data with key
     *
     * @param $data
     * @param $key
     * @return string data
     */
    public function encryptData($data, $key = null): string
    {
        $key = is_null($key) ? $this->parameterBag->get('app_secret') : $key;
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($encrypted . '::' . base64_encode($iv));
    }

    /**
     * decryption of data with key
     *
     * @param data
     * @param key
     * @return openssl_decrypt data
     */
    protected function decryptData($data, $key): string
    {
        list($encrypted_data, $iv) = explode('::', base64_decode(urldecode($data)), 2);
        $iv = base64_decode($iv);
        if (strlen($iv) !== openssl_cipher_iv_length('aes-256-cbc')) {
            $iv = substr(urldecode($iv), 0, 16);
        }
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
    }

    /**
     * To encrypt the email address of the user for confirmation
     *
     * @param string $encryptedString
     * @return openssl_decrypt data
     */
    public function decryptEmail(string $encryptedString): string
    {
        $key = $this->parameterBag->get('app_secret');
        return $this->decryptData($encryptedString, $key);
    }

    /**
     * To check if the token is valid or not
     *
     * @param Request $request
     * @return User
     * @throws \Exception
     * @throws UserNotFoundException
     */
    public function validateToken(Request $request): User
    {
        $encryptedString = urldecode($request->get('token'));
        $decryptData = $this->decryptEmail($encryptedString);
        list($email, $tokenSentTimeStamp) = explode('#', $decryptData, 2);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('notValidEmail');
        }
        $em = $this->doctrine->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['property' => $email]);
        if (!$user instanceof User) {
            throw new UserNotFoundException('userNotFound', 400);
        }
        if ($user->getConfirmationToken() !== urlencode($encryptedString)) {
            throw new \Exception('invalidToken');
        }
        $curDate = new \DateTime();
        if ($curDate->getTimestamp() > (int)$tokenSentTimeStamp) {
            throw new \Exception('tokenExpired');
        }
        return $user;
    }

    /**
     * Send confirmation mail
     * @param UserIdentity $userIdentity
     * @param string $templateName
     * @param string $userLanguage
     * @param string $subject
     * @param string|null $userRole
     * @param array|null $params
     * @param bool $isForgotPassword
     * @param bool $isPasswordRequest
     * @param array $messageParams
     * @param bool|null $adminInvitation
     * @param bool|null $companySelfRegistration
     * @return void
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws \Exception
     */
    public function sendEmailConfirmation(UserIdentity $userIdentity, string $templateName, string $userLanguage, string $subject,
                                          string $userRole = null, array $params = null, bool $isForgotPassword = FALSE,
                                          bool $isPasswordRequest = false, array $messageParams = [], ?bool $adminInvitation = false,
                                          ?bool $companySelfRegistration = false): void
    {
        $em = $this->doctrine->getManager();
        if (($userRole === Constants::COMPANY_ROLE && !$companySelfRegistration) || $userRole === Constants::INDIVIDUAL || true === $adminInvitation) {
            $this->sendCompanyEmailConfirmation($userIdentity, $templateName, $userLanguage, $subject, $params);
        } elseif ($userRole === Constants::GUEST_ROLE) {
            $this->sendGuestEmailConfirmation($userIdentity, $templateName, $userLanguage, $subject);
        } else {
            $params = $this->activationUrlBuilder($userIdentity->getUser(), $isForgotPassword, $userLanguage);
            $userIdentity->getUser()->setConfirmationToken($params['queryString'])
                ->setIsTokenVerified(false);
            if ($isPasswordRequest) {
                $userIdentity->getUser()->setPasswordRequestedAt(new \DateTime());
            }
            $em->flush();
            $params['locale'] = $userLanguage;
            $params['userIdentity'] = $userIdentity;
            $template = $this->templateHelper->renderEmailTemplate($templateName, $params);
            $emailSubject = $this->translator->trans($subject, $messageParams, null, $userLanguage);
            $this->emailServiceHelper->sendEmail($emailSubject, $template,
                $this->parameterBag->get('from_email'), $userIdentity->getUser()->getProperty());
        }
    }

    /**
     * Activation URL builder
     *
     * @param User $user
     * @param bool $isForgotPassword
     * @param string $userLanguage
     * @param boolean $setValidity
     *
     * @return array
     * @throws \Exception
     */
    private function activationUrlBuilder(User $user, bool $isForgotPassword, string $userLanguage, bool $setValidity = true): array
    {
        $key = $this->parameterBag->get('app_secret');
        $dataToEncrypt = null;
        if ($setValidity === true) {
            $validity = $this->parameterBag->get('email_validity_ttl');
            $curTime = new \DateTime('now');
            $expiryTime = $curTime->add(new \DateInterval($validity));
            $dataToEncrypt = $user->getProperty() . '#' . $expiryTime->getTimestamp();
        }
        $data['queryString'] = urlencode($this->encryptData($dataToEncrypt, $key));
        $data['url'] = $this->getUrl($data['queryString'], $userLanguage, $isForgotPassword);;
        return $data;
    }

    /**
     * More URL builder
     *
     * @param User $user
     * @param string $userLanguage
     * @param boolean $setValidity
     *
     * @return array
     * @throws \Exception
     */
    public function moreUrlBuilder(User $user, string $userLanguage, bool $setValidity = false): array
    {
        $key = $this->parameterBag->get('app_secret');
        $dataToEncrypt = null;
        if ($setValidity === true) {
            $validity = $this->parameterBag->get('email_validity_ttl');
            $curTime = new \DateTime('now');
            $expiryTime = $curTime->add(new \DateInterval($validity));
            $dataToEncrypt = $user->getProperty() . '#' . $expiryTime->getTimestamp();
        } else {
            $dataToEncrypt = $user->getProperty();
        }
        $data['queryString'] = urlencode($this->encryptData($dataToEncrypt, $key));
        $data['url'] = $this->getMoreContents($data['queryString'], $userLanguage);
        return $data;
    }

    /**
     * @param UserIdentity $userIdentity
     * @param string $templateName
     * @param string $userLanguage
     * @param string $subject
     * @param array $parameters
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function sendCompanyEmailConfirmation(UserIdentity $userIdentity, string $templateName, string $userLanguage, string $subject, array $parameters): void
    {
        $parameters['email'] = $userIdentity->getUser()->getProperty();
        $parameters['userFirstName'] = $userIdentity->getFirstName();
        $parameters['userLastName'] = $userIdentity->getLastName();
        $parameters['locale'] = $userLanguage;
        $parameters['loginUrl'] = $this->parameterBag->get('FE_DOMAIN') . $this->parameterBag->get('web_login_url');
        $template = $this->templateHelper->renderEmailTemplate($templateName, $parameters);
        $emailSubject = $this->translator->trans($subject, [], null, $userLanguage);
        $this->emailServiceHelper->sendEmail($emailSubject, $template,
            $this->parameterBag->get('from_email'), $userIdentity->getUser()->getProperty());
    }

    /**
     * sendGuestEmailConfirmation
     *
     * @param UserIdentity $userIdentity
     * @param string $templateName
     * @param string $userLanguage
     * @param string $subject
     * @param array $parameters
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function sendGuestEmailConfirmation(UserIdentity $userIdentity, string $templateName, string $userLanguage,
                                                string $subject, array $parameters = []): void
    {
        $parameters['userIdentity'] = $userIdentity;
        $parameters['locale'] = $userLanguage;
        $template = $this->templateHelper->renderEmailTemplate($templateName, $parameters);
        $emailSubject = $this->translator->trans($subject, [], null, $userLanguage);
        $this->emailServiceHelper->sendEmail($emailSubject, $template,
            $this->parameterBag->get('from_email'), $userIdentity->getUser()->getProperty());
    }

    /**
     *
     * @param UserIdentity $userIdentity
     * @param string $templateName
     * @param string $userLanguage
     * @param string $subject
     * @param array $parameters
     * @return void
     * @throws
     */
    public function sendEmail(UserIdentity $userIdentity, string $templateName, string $userLanguage, string $subject, array $parameters): void
    {
        $parameters['userFirstName'] = $userIdentity->getFirstName();
        $parameters['userLastName'] = $userIdentity->getLastName();
        $parameters['locale'] = $userLanguage;
        $template = $this->templateHelper->renderEmailTemplate($templateName, $parameters);
        $emailSubject = $this->translator->trans($subject, [], null, $userLanguage);
        $this->emailServiceHelper->sendEmail($emailSubject, $template,
            $this->parameterBag->get('from_email'), $userIdentity->getUser()->getProperty());
    }

    /**
     *
     * @param array $params
     * @param array $deviceIds
     * @return void
     */
    public function sendPushNotification(array $params, array $deviceIds): void
    {
        $this->pushNotificationService->sendPushNotification($params, $deviceIds);
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    public function getLocale(Request $request): string
    {
        return ($request->headers->get('locale')) ? $request->headers->get('locale') : $this->parameterBag->get('default_language');
    }

    /**
     * Function to get notification url
     *
     * @param string $event
     * @param string|null $id
     * @return string
     */
    public function getEventUrl(string $event, ?string $id): ?string
    {
        $url = null;
        $em = $this->doctrine->getManager();
        $status = $em->getRepository(DamageStatus::class)->findOneBy(['key' => $event]);
        if (null !== $status && null !== $id) {
            $url = $this->parameterBag->get('damage_view_path') . $id;
        }

        return $url;
    }

    /**
     * Function to get EntityManagerInterface
     *
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManager();
    }

    /**
     * Function to get ParameterBagInterface
     *
     * @return ParameterBagInterface
     */
    public function getParameterBag(): ParameterBagInterface
    {
        return $this->parameterBag;
    }

    /**
     * Function to get TranslatorInterface
     *
     * @return TranslatorInterface
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     *
     * @param string $role
     * @return Role|null
     */
    public function getRoleByKey(string $role): ?Role
    {
        $em = $this->doctrine->getManager();
        $oRole = $em->getRepository(Role::class)->findOneBy(['roleKey' => $role, 'deleted' => false]);
        if ($oRole instanceof Role) {
            return $oRole;
        }

        return null;
    }

    /**
     *
     * @return SecurityService
     */
    public function getSecurityService(): SecurityService
    {
        return $this->securityService;
    }

    /**
     * @param string $email
     * @param Damage $damage
     * @param string $locale
     * @param string|null $subject
     * @param string|null $portalUrl
     * @param bool $isEdit
     * @param string|null $user
     * @return bool
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendNonRegisteredCompanyEmailNotification(string $email, Damage $damage, string $locale,
                                                              string $subject = null, string $portalUrl = null,
                                                              bool $isEdit = false, ?string $user = null): void
    {
        $parameters['locale'] = $locale;
        $parameters['damageUrl'] = $portalUrl;
        $parameters['damage'] = $damage;
        $emailSubject = $this->translator->trans($subject, [], null, $locale);
        if ($isEdit) {
            $parameters['damageUrl'] = $portalUrl . '/' . $user;
        }
        if (is_null($subject)) {
            $subject = $isEdit ? 'updateNonRegisteredEmailSubject' : 'nonRegisteredEmailSubject';
            $emailSubject = $this->translator->trans($subject, [], null, $locale);
        }
        $templateName = ($isEdit == true) ? 'UpdateNonRegisteredCompanyEmail' : 'NonRegisteredCompanyEmail';
        $template = $this->templateHelper->renderEmailTemplate($templateName, $parameters);
        $this->emailServiceHelper->sendEmail($emailSubject, $template, $this->parameterBag->get('from_email'), $email);
    }
}