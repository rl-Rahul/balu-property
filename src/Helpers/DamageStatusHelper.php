<?php

/**
 * This file is part of the Balu 2.0 package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Helpers;

use App\Utils\Constants;
use Doctrine\ORM\QueryBuilder;

/**
 * Helper file to handle damage status
 *
 * Template manager actions.
 *
 * @package         Balu Property App 2
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class DamageStatusHelper
{
    /**
     * @param QueryBuilder $qb
     * @param array $params
     * @param string $currentRole
     * @return array
     */
    public static function getOpenDamageStatuses(QueryBuilder $qb, array $params, string $currentRole): array
    {
        switch ($currentRole) {
            case Constants::COMPANY_ROLE:
            case Constants::COMPANY_USER_ROLE:
                $damageStatuses = Constants::OPEN_DAMAGES_FOR_COMPANY_AND_COMPANY_USER_ROLE;
                $allocatedDamages = $damageStatuses;
                break;
            case Constants::PROPERTY_ADMIN_ROLE:
            case Constants::OWNER_ROLE:
            case Constants::JANITOR_ROLE:
                $damageStatuses = Constants::OPEN_DAMAGES_FOR_OWNER_AND_ADMIN;
                $allocatedDamages = Constants::CHECK_ALLOCATION_TYPE_OPEN_DAMAGES;
                break;
            default:
                $damageStatuses = Constants::OPEN_DAMAGES_FOR_TENANT_AND_OBJECT_OWNER;
                $allocatedDamages = $damageStatuses;
        }

        if (isset($params['status']) && $params['status'] == 'open') {
            $qb->andWhere('s.key NOT IN (:closedDamage) AND (d.allocation = 1 and s.key IN (:allocatedDamages) OR (d.allocation = 0 AND s.key IN (:status)))');
            $qb->setParameter('closedDamage', Constants::CLOSE_DAMAGES)
                ->setParameter('allocatedDamages', $allocatedDamages);
        }

        return $damageStatuses;
    }

    /**
     * @param QueryBuilder $qb
     * @param array $params
     * @param string $currentRole
     * @return array
     */
    public static function getCloseDamageStatuses(QueryBuilder $qb, array $params, string $currentRole): array
    {
        switch ($currentRole) {
            case Constants::COMPANY_ROLE:
            case Constants::COMPANY_USER_ROLE:
            case Constants::PROPERTY_ADMIN_ROLE:
            case Constants::OWNER_ROLE:
            case Constants::JANITOR_ROLE:
                $damageStatuses = Constants::CLOSE_DAMAGES;
                $allocatedDamages = $damageStatuses;
                break;
            default:
                $damageStatuses = Constants::CLOSE_DAMAGES;
                $allocatedDamages = $damageStatuses;
        }

        if (isset($params['status']) && $params['status'] !== 'open') {
            $qb->andWhere('s.key IN (:closedDamage) AND (d.allocation = 1 and s.key IN (:allocatedDamages) OR (d.allocation = 0 AND s.key IN (:status)))');
            $qb->setParameter('closedDamage', Constants::CLOSE_DAMAGES)
                ->setParameter('allocatedDamages', $allocatedDamages);
        }

        return $damageStatuses;
    }
}