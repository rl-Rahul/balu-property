<?php

/**
 * This file is part of the BaluProperty package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use App\Entity\Damage;
use App\Entity\Property;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * PhaseVoter
 *
 * Access control for damage object
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class DamageVoter extends PropertyVoter
{
    /**
     * Entity to vote
     */
    const VOTING_ENTITY = 'DAMAGE';

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
        if (!$this->checkAttributeSupports($attribute) || !$subject instanceof Damage) {
            return false;
        }
        $property = $subject->getApartment()->getProperty();
        if ($property instanceof Property) {
            $this->setSubject($property);
            
            return true;
        }
        
        return false;
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
    
    /**
     * validate edit attribute
     *
     * @param string $permission_name
     * @param array $permissions
     * @return boolean
     */
    
    protected function validateEdit(string $permission_name, array $permissions): bool
    {
        if (in_array(strtoupper($permission_name), $permissions)) {
            return true;
        }
        return false;
    }
    
    /**
     * validate create attribute
     *
     * @param string $permission_name
     * @param array $permissions
     * @return boolean
     */
    
    protected function validateCreate(string $permission_name, array $permissions): bool
    {
        return $this->validateEdit($permission_name, $permissions);
    }
    
    /**
     * validate delete attribute
     *
     * @param string $permission_name
     * @param array $permissions
     * @return boolean
     */
    protected function validateDelete(string $permission_name, array $permissions): bool
    {
        return $this->validateEdit($permission_name, $permissions);
    }
    
    /**
     * validate manage attribute
     *
     * @param string $permission_name
     * @param array $permissions
     * @return boolean
     */
    protected function validateManage(string $permission_name, array $permissions): bool
    {
        return $this->validateEdit($permission_name, $permissions);
    }
}
