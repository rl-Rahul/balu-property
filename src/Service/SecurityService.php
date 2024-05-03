<?php

/**
 * This file is part of the Wedoit Project package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use App\Entity\Damage;
use App\Entity\Permission;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserIdentity;
use App\Entity\UserPropertyPool;
use App\Utils\Constants;
use App\Utils\GeneralUtility;
use Doctrine\Persistence\ManagerRegistry;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Entity\UserDevice;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Nyholm\Psr7\ServerRequest;

/**
 * SecurityService
 *
 * Service class to handle secured features
 *
 * @package         PITS
 * @subpackage      App
 * @author          Rahul <rahul.rl@pitsolutions.com>
 */
class SecurityService extends BaseService
{
    /**
     * @var AuthorizationServer
     */
    private AuthorizationServer $server;

    /**
     * @var ParameterBagInterface $params
     */
    private ParameterBagInterface $params;

    /**
     * @var ManagerRegistry $doctrine
     */
    private ManagerRegistry $doctrine;

    /**
     * @var TokenStorageInterface $tokenStorage
     */
    private TokenStorageInterface $tokenStorage;

    /**
     * @var UserPasswordHasherInterface $passwordHasher
     */
    private UserPasswordHasherInterface $passwordHasher;

    /**
     * Constructor
     * @param AuthorizationServer $server
     * @param ParameterBagInterface $params
     * @param ManagerRegistry $doctrine
     * @param TokenStorageInterface $tokenStorage
     * @param UserPasswordHasherInterface $passwordHasher
     */
    public function __construct(AuthorizationServer $server, ParameterBagInterface $params, ManagerRegistry $doctrine,
                                TokenStorageInterface $tokenStorage, UserPasswordHasherInterface $passwordHasher)
    {
        $this->server = $server;
        $this->params = $params;
        $this->doctrine = $doctrine;
        $this->tokenStorage = $tokenStorage;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * Function to process login action
     *
     * @param Request $request
     * @param ServerRequest $serverRequest
     * @param ResponseFactoryInterface $responseFactory
     * @param GeneralUtility $generalUtility
     * @param DamageService $damageService
     * @param CompanyService $companyService
     * @param string|null $class
     * @param bool $isGuest
     * @param string|null $locale
     * @return array
     */
    public function loginProcess(Request $request, ServerRequest $serverRequest, ResponseFactoryInterface $responseFactory,
                                 GeneralUtility $generalUtility, DamageService $damageService, CompanyService $companyService,
                                 string $class = null, bool $isGuest = false, ?string $locale = 'en'): array
    {
        $serverResponse = $responseFactory->createResponse();
        $em = $this->doctrine->getManager();
        try {
            foreach ($this->getServerClientDetails($request) as $key => $value) {
                $request->request->set($key, $value);
            }
            $this->checkIsSuperAdminUser($request, $class);
            $this->checkUserIsEnabled($request, $isGuest);
            $authorize = $this->server->respondToAccessTokenRequest(
                $serverRequest->withParsedBody($request->request->all()),
                $serverResponse
            );
            $json = json_decode($authorize->getBody()->__toString(), true);
            $userObj = $em->getRepository(UserIdentity::class)->findOneByEmail($request->request->get('username'));
            if (!$isGuest) {
                $json['is_first_login'] = $this->checkIsFirstLogin($request);
                $this->setFirstLogin($request);
                $this->setLastLogin($request);
                $this->saveUserDeviceDetails($request);
                $json['language'] = $userObj->getLanguage() ?? $this->params->get('default_language');
            }
            if ($request->request->has('damage') && $request->request->get('damage') !== null) {
                $damage = $em->getRepository(Damage::class)->findOneBy(['publicId' => $request->request->get('damage')]);
                $params['company'] = [$userObj->getPublicId()];
                $statusKey = $damage->getCompanyAssignedByRole() instanceof UserIdentity ?
                    strtoupper($damage->getCompanyAssignedByRole()->getRoleKey()) :
                    strtoupper($damage->getCreatedByRole()->getRoleKey());
                $params['status'] = $statusKey . '_SEND_TO_COMPANY_WITHOUT_OFFER';
                $params['damage'] = $request->request->get('damage');
                $companyService->saveDamageRequest($params, $this->passwordHasher);
                $damageService->logDamage($userObj, $damage, null, null, null, $params['company']);
            }
            $locale = isset($json['language']) ? $json['language'] : $locale;
            $json['roles'] = $this->getRoles($request->request->get('username'), $isGuest, $locale);
            $json['designation'] = in_array(Constants::ROLE_ADMIN, $userObj->getUser()->getRoles()) ? 'admin' : 'user';
            $data = $generalUtility->handleSuccessResponse('loginSuccess', $json);
        } catch (OAuthServerException | AccessDeniedException |
        CustomUserMessageAuthenticationException | UserNotFoundException |
        \Exception $e) {
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $data;
    }

    /**
     * Function to get server client details
     *
     * @param Request $request
     * @return array
     */
    private function getServerClientDetails(Request $request): array
    {
        $details = [];
        $property = $request->request->get('username');
        $em = $this->doctrine->getManager();
        $type = filter_var($property, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $pool = $em->getRepository(UserPropertyPool::class)
            ->findOneBy(['property' => $property, 'type' => $type, 'revoked' => false, 'deleted' => false]);
        if (!$pool instanceof UserPropertyPool) {
            throw new UserNotFoundException('userNotFound'); // change the texts accordingly
        }
        if (!$pool->getIsPrimary()) {
            $pool = $em->getRepository(UserPropertyPool::class)
                ->findOneBy(['user' => $pool->getUser(), 'isPrimary' => true, 'revoked' => false, 'deleted' => false]);
            if (!$pool instanceof UserPropertyPool) {
                throw new UserNotFoundException('primaryUserNotFound');
            }
        }
        $client = $em->find(Client::class, $this->params->get('app.client.id'));
        if ($client instanceof Client) {
            $details['client_id'] = $client->getIdentifier();
            $details['client_secret'] = $client->getSecret();
            $details['grant_type'] = $request->request->has('grant_type') ?
                $request->request->get('grant_type') : 'password';
            $details['username'] = $pool->getUser()->getProperty();
        }
        return $details;
    }

    /**
     * Function to check the login requested user is enabled and not deleted
     *
     * @param Request $request
     * @param bool $isGuest
     * @return void
     */
    private function checkUserIsEnabled(Request $request, bool $isGuest): void
    {
        $em = $this->doctrine->getManager();
        $user = $em->getRepository(User::class)
            ->findOneBy(['property' => $request->request->get('username')]);
        if (!$user instanceof User) {
            throw new UserNotFoundException('noUserFound', 400);
        }
        $userIdentity = $em->getRepository(UserIdentity::class)->findOneBy(['user' => $user]);
        if (!$userIdentity instanceof UserIdentity) {
            throw new UserNotFoundException('noUserFound', 400);
        }
//        if (!$isGuest && $userIdentity->getIsGuestUser()) {
//            throw new UserNotFoundException('noUserFound', 400);
//        }
        if ($userIdentity->getDeleted()) {
            throw new CustomUserMessageAuthenticationException('userDeleted', [], 401);
        }
        if (!$isGuest && !$userIdentity->getEnabled()) {
            throw new CustomUserMessageAccountStatusException('userNotEnabled', [], 401);
        }
    }

    /**
     * Function to check the login is first or not
     *
     * @param Request $request
     * @return bool
     */
    private function checkIsFirstLogin(Request $request): bool
    {
        $firstLogin = false;
        $em = $this->doctrine->getManager();
        $accessTokenCount = count($em->getRepository(AccessToken::class)
            ->findBy(['userIdentifier' => $request->request->get('username')]));
        if ($accessTokenCount === 1) {
            $user = $em->getRepository(User::class)->findBy(['property' => $request->request->get('username')]);
            if ($user instanceof User) {
                $user->setFirstLogin(new \DateTime('now'));
                $em->flush();
            }
            $firstLogin = true;
        }
        return $firstLogin;
    }


    /**
     * Function to get the user roles while user login
     *
     * @param string $username
     * @param bool $isGuest
     * @param string|null $locale
     * @return array
     */
    public function getRoles(string $username, bool $isGuest = false, ?string $locale = 'en'): array
    {
        $em = $this->doctrine->getManager();
        $method = !$isGuest ? 'getUserRoles' : 'getGuestUserRole';
        $roles = $em->getRepository(User::class)->$method(['property' => $username, 'deleted' => false], $locale);
        if (count($roles) === 1 && empty($roles[0]['roleKey'])) {
            unset($roles);
            $roles[] = ['name' => 'user', 'roleKey' => 'user', 'sortOrder' => 1];
        }

        return $roles;
    }

    /**
     * To check if the password and confirm password are same
     *
     * @param Request $request
     * @return bool
     */
    public function checkPasswordMatch(Request $request): bool
    {
        if (
            $request->request->get('password') === '' ||
            $request->request->get('confirmPassword') === ''
        ) {
            return false;
        }
        if ($request->request->get('password') !== $request->request->get('confirmPassword')) {
            return false;
        }
        return true;
    }

    /**
     * Get Current user in Service
     *
     * @return UserIdentity|null $user
     */
    public function getUser(): ?UserIdentity
    {
        $token = $this->tokenStorage->getToken();
        if ($token) {
            $user = $this->doctrine->getRepository(UserIdentity::class)->findOneBy(['user' => $token->getUser()]);
            if (!$user instanceof UserIdentity) {
                return null;
            }
            return $user;
        } else {
            return null;
        }
    }

    /**
     * Get role and permissions of given user
     *
     * @param UserIdentity $user
     * @param string|null $currentRole
     * @return array
     */
    public function getPermissions(UserIdentity $user, ?string $currentRole = null): array
    {
        $roles = array();
        $rolePermissions = array();
        if (!is_null($currentRole)) {
            $permissions = $this->doctrine->getRepository(Permission::class)->checkPermissionsOfCurrentLoggedInUser($user, $currentRole);
            if (!empty($permissions)) {
                foreach ($permissions as $permission) {
                    $rolePermissions[] = $permission->getName();
                }
            }
        } else {
            foreach ($user->getRole() as $roleUser) {
                $roles[] = $roleUser;
            }
            if (!empty($roles)) {
                foreach ($roles as $role) {
                    foreach ($role->getPermission() as $permission) {
                        $rolePermissions[] = $permission->getName();
                    }
                }
            }
        }

        return $rolePermissions;
    }

    /**
     * get User Role
     *
     * @param UserIdentity $user
     * @param string|null $locale
     * @return array
     */
    public function fetchUserRole(UserIdentity $user, ?string $locale = 'en'): array
    {
        $roles = array();
        foreach ($user->getRole() as $key => $userRole) {
            $roles['key'][] = $userRole->getRoleKey();
            $roles['name'][] = ($locale == 'de') ? $userRole->getNameDe() : $userRole->getName();
        }
        return $roles;
    }

    /**
     * check if logged in user an admin of a user
     *
     * @param integer $userId
     * @return bool
     */
    public function loggedInUserValidAdminOf(int $userId): bool
    {
        $loggedInUser = $this->getUser();
        return $this->doctrine->getRepository(User::class)->userValidAdmin($userId, $loggedInUser->getId());
    }

    /**
     * Function to save user device details
     *
     * @param Request $request
     *
     * @return bool
     */
    public function saveUserDeviceDetails(Request $request): bool
    {
        if (null !== $request->request->get('deviceId') && !empty(trim($request->request->get('deviceId')))) {
            $deviceId = trim($request->request->get('deviceId'));
            $user = $this->doctrine->getRepository(User::class)->findOneBy(['property' => $request->request->get('username'), 'deleted' => false]);
            if ($userDeviceObj = $this->doctrine->getRepository(UserDevice::class)->findBy(['deviceId' => $deviceId, 'deleted' => 0])) {
                foreach ($userDeviceObj as $userDevice) {
                    $userDevice->setDeleted(1);
                }
            }
            $this->doctrine->getRepository(UserDevice::class)->saveUserDeviceInfo($deviceId, $user->getUserIdentity());
        }

        return true;
    }

    /**
     *
     * @param Request $request
     * @return void
     */
    public function setFirstLogin(Request $request): void
    {
        $em = $this->doctrine->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['property' => $request->request->get('username'), 'deleted' => false]);
        if ($user instanceof User) {
            if (empty($user->getFirstLogin())) {
                $user->setFirstLogin(new \DateTime("now"));
                $em->flush();
            }
        }

        return;
    }

    /**
     *
     * @param Request $request
     * @return void
     */
    public function setLastLogin(Request $request): void
    {
        $em = $this->doctrine->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['property' => $request->request->get('username'), 'deleted' => false]);
        if ($user instanceof User) {
            $user->setLastLogin(new \DateTime("now"));
            $em->flush();
        }

        return;
    }

    /**
     * Function to check whether the requested user is admin role or not
     *
     * @param Request $request
     * @param string|null $class
     * @return void
     */
    private function checkIsSuperAdminUser(Request $request, ?string $class): void
    {
        if (!is_null($class) && explode('\\', $class)[2] == 'SuperAdminController') {
            $em = $this->doctrine->getManager();
            $userRepository = $em->getRepository(User::class);
            $user = $userRepository->findOneBy(['property' => $request->request->get('username'), 'deleted' => false]);
            $admin = $em->getRepository(UserIdentity::class)->findOneBy(['user' => $user, 'deleted' => false]);
            if (!$admin instanceof UserIdentity) {
                throw new UserNotFoundException('adminUserNotFound');
            }
            $role = $em->getRepository(Role::class)->findOneBy(['roleKey' => 'admin']);
            if (!$admin->getRole()->contains($role)) {

                throw new AccessDeniedException('notAdminUser');
            }
        }
    }
}