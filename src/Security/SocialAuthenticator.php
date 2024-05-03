<?php

/*
 * This file is part of the Wedoit Project package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace App\Security;

use App\Entity\User; // your user entity
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator as BaseSocialAuthenticator; 
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Token\AppleAccessToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use League\OAuth2\Client\Token\AccessToken;

/**
 * SocialAuthenticator
 *
 * @package         Wedoit
 * @subpackage      App
 * @author          Nixon Fernandez<nixon.fz@pitsolutions.com>
 */
class SocialAuthenticator extends BaseSocialAuthenticator
{
    /**
     * @var ClientRegistry
     */
    private $clientRegistry;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var UserPasswordHasherInterface
     */
    private $passwordHasher;
    /**
     * @var string
     */
    private $resource;
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var SecurityService
     */
    private $securityService;


    /**
     * Constructor function to initialize some instances to make this social authenticator work
     *
     * @param ClientRegistry $clientRegistry
     * @param EntityManagerInterface $em
     * @param RouterInterface $router
     * @param UserPasswordHasherInterface $passwordHasher
     * @param SessionInterface $session
     * @param SecurityService $securityService
     */
    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $em,
                                RouterInterface $router, UserPasswordHasherInterface $passwordHasher,
                                SessionInterface $session, SecurityService $securityService)
    {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->router = $router;
        $this->passwordHasher = $passwordHasher;
        $this->resource = '';
        $this->session = $session;
        $this->securityService = $securityService;
    }

    /**
     * Function to check whether this route matches
     * social login route which is configured in our system
     *
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request): bool
    {
        return $this->resourceChecker($request);
    }

    /**
     * Function to get the credentials from resources with the help of
     * access_token and resource_owner_id which holds the access token
     * social login route which is configured in our system
     *
     * @param Request $request
     * @return AccessToken
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function getCredentials(Request $request)
    {
        // this method is only called if supports() returns true
        $params = array();
        $requestParams = $request->toArray();
        if ($requestParams['type'] === 'apple') {
            $accessTokenObject = $this->getAuthClient()->getOAuth2Provider()->getAccessToken('authorization_code', ['code' => $requestParams['code']]);
            if (!$accessTokenObject instanceof AppleAccessToken) {
                return null;
            }
            $params['access_token'] = $accessTokenObject->getToken();
            $params['resource_owner_id'] = $accessTokenObject->getResourceOwnerId();
        } else {
            $params['access_token'] = $requestParams['code'];
            $params['resource_owner_id'] = $requestParams['type'];
        }
        return new AccessToken($params);
    }

    /**
     * Function to get the user instance
     * from access token and the fetched user is checked in our db
     *
     * @param $credentials
     * @param UserProviderInterface $userProvider
     * @return User
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?User
    {
        $socialUser = $this->getAuthClient()->fetchUserFromToken($credentials);
        if ($this->validateEmail($socialUser)) {
            $socialUserIdentifier = $socialUser->getEmail();
            $emailAvailable = true;
        } else {
            $socialUserIdentifier = $socialUser->getId();
            $emailAvailable = false;
        }
        $criterion = $emailAvailable ? ['email' => $socialUserIdentifier] :
                        ['socialMediaUuid' => $socialUserIdentifier, 'socialMediaType' => $credentials->getResourceOwnerId()];
        $user = $this->em->getRepository(User::class)->findOneBy($criterion);
        // 1) if user exists, return existing user
        if ($user instanceof User) {
            // 2) if deleted user do not authenticate or if user is blocked, do not authenticate
            if ($user->getDeleted() || $user->getIsBlocked()) {
                return null;
            }
            $this->session->set('uid', $user->getIdentifier()->jsonSerialize());
            $this->session->set('email', $user->getEmail());
            return $user;
        }
        // 4) if we have no user available in the system, register it
        $nUser = new User();
        $nUser->setPassword($this->passwordHasher->hashPassword($nUser, rand(10, 14)))
                ->setEmail($socialUserIdentifier)
                ->setEnabled(true)
                ->setFirstName($socialUser->getFirstName())
                ->setLastName($socialUser->getLastName())
                ->setSocialMediaType($credentials->getResourceOwnerId())
                ->setSocialMediaUuid($socialUser->getId())
                ->setIsSocialLogin(TRUE)
                ->setRoles(['ROLE_USER']);
        if (!$emailAvailable) {
            $nUser->setIsEmailAvailable(FALSE);
        }

        $this->em->persist($nUser);
        $this->em->flush();
        $this->session->set('uid', $nUser->getIdentifier()->jsonSerialize());
        $this->session->set('email', $nUser->getEmail());

        return $nUser;
    }

    /**
     * Function to check whether the email is valid or not
     *
     * @param $socialUser
     * @return bool
     */
    private function validateEmail($socialUser): bool
    {
        if (null === $socialUser->getEmail()) {
            return false;
        }
        if (!filter_var($socialUser->getEmail(), FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return true;
    }

    /**
     * Function returns when user successfully authenticates by our system.
     * User details will be provided by the resource owners and we just log them what we want
     * Rest of the authentication and access_token, refresh_tokens will be provided by the respective route
     *
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey
     * @return string
     * @throws \Exception
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return;
    }

    /**
     * Function returns on failure of social authentication
     *
     * @param Request $request
     * @param AuthenticationException $exception
     * @return Response|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        $data = ['error' => true, 'message' => strtr($exception->getMessageKey(), $exception->getMessageData())];
        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     *
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return RedirectResponse
     */
    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
    {
        return new RedirectResponse(
            '/connect/', // might be the site, where users choose their oauth provider
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    /**
     * Function to check whether this route matches social login route
     *
     * This redirects to the 'login'.
     * @param Request $request
     * @return bool
     */
    private function resourceChecker(Request $request): bool
    {
        if ($request->attributes->get("_route") === 'wdi_social_login') {
            $requestParams = $request->toArray();
            if (!empty($requestParams)) {
                $this->resource = $requestParams['type'];
                return true;
            }
        }
        return false;
    }

    /**
     * Function to get the resource from which the system gets the authorization code
     * Resources owners needs to configure in config/packages/knpu_oauth2_client.yaml file
     * for example: consider configuring facebook as resource owner. Add facebook to knpu_oauth2_client.yaml
     * along with the required parameters. One thing keep in mind that whatever the resource owner, the redirect path
     * should be the same.
     *
     * @return \KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface Client
     */
    private function getAuthClient()
    {
        return $this->clientRegistry->getClient($this->resource);
    }
}
