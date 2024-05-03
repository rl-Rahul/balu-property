<?php

/**
 * This file is part of the BaluProperty package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use App\Entity\Apartment;
use App\Entity\Property;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * PhaseVoter
 *
 * Access control for apartment object
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class ApartmentVoter extends PropertyVoter
{
    /**
     * Entity to vote
     */
    const VOTING_ENTITY = 'APARTMENT';
    
    protected $apartment;

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param mixed $subject The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports($attribute, $subject): bool
    {
        if (!$this->checkAttributeSupports($attribute) || !$subject instanceof Apartment) {
            return false;
        }
        $property = $subject->getProperty();
        $this->setApartment($subject);
        if ($property instanceof Property) {
            $this->setSubject($property);
            return true;
        }
        return false;
    }
    
    /**
     * Function to set subject data.
     *
     * @param Apartment $apartment
     */
    protected function setApartment(Apartment $apartment)
    {
        $this->apartment = $apartment;
    }

    /**
     *
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return boolean
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        return parent::voteOnAttribute($attribute, $subject, $token);
    }
    
//    /**
//     * validate view attribute
//     *
//     * @param string $permission_name
//     * @param array $permissions
//     * @return boolean
//     */
//
//    protected function validateView(string $permission_name, array $permissions): bool
//    {
//        $user = $this->container->get('security.token_storage')->getToken()->getUser();
//        $isApartmentAdmin = $this->container->get('baluproperty.general.services')->isCurrentUserApartmentAdmin($this->apartment->getId());
//        $tenant = $this->entityManager->getRepository('AppBundle:BpTenant')->findOneByUser($user);
//        $role = $this->container->get('baluproperty.user.services')->fetchUserRole($user);
//        if (!empty($tenant)) {
//            $tenantApartment = $tenant->getApartment();
//        }
//        if ((in_array(strtoupper($permission_name), $permissions)) &&
//                (($this->authUser == $this->subject->getUser()) ||
//                ($isApartmentAdmin == true) ||
//                ($this->apartment == $tenantApartment) || $role == $this->container->getParameter('user_roles')['admin'])) {
//            if (!is_null($tenant) && $tenant->getActive() == 0) {
//                return false;
//            }
//            return true;
//        }
//        return false;
//    }
}
