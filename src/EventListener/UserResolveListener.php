<?php
declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use League\Bundle\OAuth2ServerBundle\Converter\UserConverter;

final class UserResolveListener
{
    /**
     * @var RequestStack $request
     */
    protected RequestStack $request;

    /**
     * @var UserProviderInterface
     */
    private UserProviderInterface $userProvider;

    /**
     * @var UserPasswordHasherInterface
     */
    private UserPasswordHasherInterface $userPasswordEncoder;

    /**
     * @var UserConverter
     */
    private UserConverter $converter;

    /**
     * @param RequestStack $request
     * @param UserProviderInterface $userProvider
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @param UserConverter $converter
     */
    public function __construct(RequestStack $request, UserProviderInterface $userProvider,
                                UserPasswordHasherInterface $userPasswordHasher, UserConverter $converter)
    {
        $this->request = $request;
        $this->userProvider = $userProvider;
        $this->userPasswordEncoder = $userPasswordHasher;
        $this->converter = $converter;
    }

    /**
     * @param UserResolveEvent $event
     */
    public function onUserResolve(UserResolveEvent $event): void
    {
        $user = $this->userProvider->loadUserByIdentifier($event->getUsername());
        if (null === $user) {
            return;
        }
        if (!in_array($this->request->getCurrentRequest()->attributes->get('_route'), ['balu_verify_guest_user', 'balu_web_login']) &&
            !$this->userPasswordEncoder->isPasswordValid($user, $event->getPassword())) {
            return;
        }
        $event->setUser($user);
    }
}