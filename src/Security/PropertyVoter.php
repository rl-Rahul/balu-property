<?php

/**
 * This file is part of the BaluProperty package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use App\Entity\Property;
use App\Entity\PropertyUser;
use App\Entity\UserIdentity;
use App\Service\SecurityService;
use App\Service\UserService;
use App\Utils\Constants;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * PropertyVoter
 *
 * Access control for BpProperty object
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class PropertyVoter extends Voter
{

    /**
     * View action
     */
    const VIEW = 'view';

    /**
     * Edit action
     */
    const EDIT = 'edit';

    /**
     * Delete action
     */
    const DELETE = 'delete';

    /**
     * Create action
     */
    const CREATE = 'create';

    /**
     * Manage action
     */
    const MANAGE = 'manage';

    const VOTING_ENTITY = 'PROPERTY';

    /**
     * Subject to check the access
     *
     * @var mixed
     */
    protected $subject = null;

    protected $authUser;

    /**
     * @var ParameterBagInterface $params
     */
    private ParameterBagInterface $params;

    /**
     * @var ManagerRegistry $doctrine
     */
    private ManagerRegistry $doctrine;

    /**
     * @var SecurityService $securityService
     */
    private SecurityService $securityService;

    /**
     * @var UserService $userService
     */
    private UserService $userService;

    /**
     * @var RequestStack $requestStack
     */
    private RequestStack $requestStack;

    /**
     * Constructor
     *
     * @param ManagerRegistry $doctrine
     * @param UserService $userService
     * @param SecurityService $securityService
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $requestStack
     */
    public function __construct(ManagerRegistry $doctrine, UserService $userService, SecurityService $securityService,
                                ParameterBagInterface $parameterBag, RequestStack $requestStack)
    {
        $this->doctrine = $doctrine;
        $this->params = $parameterBag;
        $this->securityService = $securityService;
        $this->userService = $userService;
        $this->requestStack = $requestStack;
    }


    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param mixed $subject The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports(string $attribute, $subject): bool
    {
        if (!$this->checkAttributeSupports($attribute) || !$subject instanceof Property) {
            return false;
        }
        $this->setSubject($subject);
        return true;
    }

    /**
     * Function to check if the attribute is supported.
     *
     * @param string $attribute
     * @return bool
     */
    protected function checkAttributeSupports(string $attribute): bool
    {
        return in_array($attribute, array(self::CREATE, self::VIEW, self::EDIT, self::DELETE, self::MANAGE));
    }

    /**
     * Function to set subject data.
     *
     * @param Property $property
     */
    protected function setSubject(Property $property)
    {
        $this->subject = $property;
    }

    /**
     *
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return boolean
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $this->userService->getUserIdentity($token->getUser());
        if ((!$user instanceof UserIdentity)) {
            // the user must be logged in and  must be the owner of the property; if not, deny access
            return false;
        }
        $this->authUser = $user;

        return $this->checkPermission($attribute, $user);
    }

    /**
     * Function to check the user permission of an object
     *
     * @param string $permissionName
     * @param UserIdentity $user
     * @return boolean
     */
    protected function checkPermission(string $permissionName, UserIdentity $user): bool
    {
        if ($this->subject instanceof Property && $this->subject->getDeleted()) {
            return false;
        }
        $permissions = array_unique($this->securityService->getPermissions($user, $this->requestStack->getCurrentRequest()->headers->get('currentRole')));
        if (is_array($permissions)) {
            $function = 'validate' . ucfirst($permissionName);
            $permissionName = $this->generatePermissionName($permissionName);
            if (method_exists($this, $function)) {
                return $this->$function($permissionName, $permissions);
            }
        }
        return false;
    }

    /**
     * generating the permission name.
     *
     * @param string $permissionName
     * @return string
     */
    protected function generatePermissionName(string $permissionName): string
    {
        return strtoupper($permissionName . '_' . static::VOTING_ENTITY);
    }


    /**
     * validate edit attribute
     *
     * @param string $permissionName
     * @param array $permissions
     * @return boolean
     */
    protected function validateEdit(string $permissionName, array $permissions): bool
    {
        $user_role = $this->securityService->fetchUserRole($this->authUser);
        $isPropertyAdmin = $this->userService->isPropertyAdmin($this->subject);
        if ((in_array(strtoupper($permissionName), $permissions)) &&
            ($this->authUser == $this->subject->getUser() || in_array($this->params->get('user_roles')['admin'], $user_role['key']))
            || $isPropertyAdmin == true) {
            return true;
        }
        return false;
    }

    /**
     * validate create attribute
     *
     * @param string $permissionName
     * @param array $permissions
     * @return boolean
     */

    protected function validateCreate(string $permissionName, array $permissions): bool
    {
        if (in_array(strtoupper($permissionName), $permissions)) {
            return true;
        }
        return false;
    }

    /**
     * validate delete attribute
     *
     * @param string $permissionName
     * @param array $permissions
     * @return boolean
     */
    protected function validateDelete(string $permissionName, array $permissions): bool
    {
        return $this->validateEdit($permissionName, $permissions);
    }

    /**
     * validate manage attribute
     *
     * @param string $permissionName
     * @param array $permissions
     * @return boolean
     */
    protected function validateManage(string $permissionName, array $permissions): bool
    {
        return $this->validateEdit($permissionName, $permissions);
    }

    /**
     * validate view attribute
     *
     * @param string $permission_name
     * @param array $permissions
     * @return boolean
     */
    protected function validateView(string $permission_name, array $permissions): bool
    {
        $property = [];
        $properties = [];
        $users = $this->doctrine->getRepository(PropertyUser::class)->getTenantsAndObjectOwners(['user' => $this->securityService->getUser(),
            'roles' => [Constants::TENANT_ROLE, Constants::OBJECT_OWNER_ROLE]]);
        if (!empty($users)) {
            foreach ($users as $user) {
                $property[] = $user->getObject() ? $user->getObject()->getProperty()->getId() : null;
            }
            $properties = array_values(array_unique($property));
        }
        if ((in_array(strtoupper($permission_name), $permissions)) &&
            ((in_array($this->authUser->getIdentifier(), [$this->subject->getUser()->getIdentifier(), $this->subject->getAdministrator()->getIdentifier()])) ||
                (in_array($this->subject->getIdentifier(), $properties)))) {
            return true;
        }

        return false;
    }
}
