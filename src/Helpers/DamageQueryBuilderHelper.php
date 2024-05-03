<?php

/**
 * This file is part of the Balu 2.0 package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Helpers;

use App\Entity\Apartment;
use App\Entity\DamageRequest;
use App\Entity\DamageStatus;
use App\Entity\Property;
use App\Entity\PropertyUser;
use App\Entity\UserIdentity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Helper file to build damage query
 *
 * Template manager actions.
 *
 * @package         Balu Property App 2
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class DamageQueryBuilderHelper
{
    /**
     * @param QueryBuilder $qb
     * @param UserIdentity $user
     * @param EntityManagerInterface $em
     */
    public static function applyAdminCondition(QueryBuilder $qb, UserIdentity $user, EntityManagerInterface $em): void
    {
        $propertyOwners = $em->getRepository(Property::class)->findPropertyOwners($user->getIdentifier());
        $data = $em->getRepository(Property::class)->findPropertyJanitorsForPropertyAdmins($user->getIdentifier());
        array_push($propertyOwners, $user->getIdentifier());
        $qb->join('p.administrator', 'ad');
        $qb->where('p.administrator IN (:owners) OR (p.user IN (:owners) AND p.administrator IN (:administrators)) OR (p.janitor IN (:janitors) AND p.identifier IN (:property))');
        $qb->setParameter('owners', $propertyOwners)
            ->setParameter('janitors', $data['janitor'])
            ->setParameter('property', $data['property'])
            ->setParameter('administrators', $user->getIdentifier());
    }

    /**
     * @param QueryBuilder $qb
     * @param UserIdentity $user
     * @param EntityManagerInterface $em
     */
    public static function applyOwnerCondition(QueryBuilder $qb, UserIdentity $user, EntityManagerInterface $em): void
    {
        $propertyAdmins = $em->getRepository(Property::class)->findPropertyAdmins($user->getIdentifier());
        $janitors = $em->getRepository(Property::class)->findPropertyJanitorsOfOwners($user->getIdentifier());
        array_push($propertyAdmins, $user->getIdentifier());
        $apartments = $em->getRepository(Apartment::class)->getOwnerApartmentIdentifiers($user->getIdentifier(), $propertyAdmins);
        $qb->join('p.user', 'ad')
            ->where('(d.user IN (:owners) OR ad.administrator IN (:owners) OR d.damageOwner IN (:owners) OR p.janitor IN (:janitors)) AND d.apartment IN (:apartment)');
        $qb->setParameter('apartment', $apartments)
            ->setParameter('owners', $propertyAdmins)
            ->setParameter('janitors', $janitors);
    }

    /**
     * @param QueryBuilder $qb
     * @param UserIdentity $user
     * @param EntityManagerInterface $em
     */
    public static function applyJanitorCondition(QueryBuilder $qb, UserIdentity $user, EntityManagerInterface $em): void
    {
        $users = $em->getRepository(Property::class)->findPropertyOwnersAndAdminsForJanitor($user->getIdentifier());
        $qb->join('p.janitor', 'ad');
        $qb->where('p.janitor IN (:janitor) OR (p.user IN (:owners) AND p.administrator IN (:administrators))');
        $qb->setParameter('janitor', $users['janitor'])
            ->setParameter('administrators', $users['admin'])
            ->setParameter('owners', $users['owner']);
    }

    /**
     * @param QueryBuilder $qb
     * @param UserIdentity $user
     * @param array $params
     */
    public static function applyCompanyCondition(QueryBuilder $qb, UserIdentity $user, array $params): void
    {
        $qb->leftJoin(DamageRequest::class, 'dr', 'WITH', 'dr.damage = d.identifier')
            ->leftJoin(DamageStatus::class, 'ds', 'WITH', 'dr.status = ds.identifier AND ds.key IN (:status)')
            ->where('dr.company = :company AND s.key IN (:status)')
            ->setParameter('company', $user);
    }

    /**
     * @param QueryBuilder $qb
     * @param UserIdentity $user
     * @param array $params
     */
    public static function applyCompanyUserCondition(QueryBuilder $qb, UserIdentity $user, array $params): void
    {
        $qb->leftJoin(DamageRequest::class, 'dr', 'WITH', 'dr.damage = d.identifier')
            ->leftJoin(DamageStatus::class, 'ds', 'WITH', 'dr.status = ds.identifier AND ds.key IN (:status)')
            ->where('dr.company = :company AND s.key IN (:status)')
            ->setParameter('company', $user->getParent());
    }

    /**
     * @param QueryBuilder $qb
     * @param UserIdentity $user
     * @param string $currentRole
     * @param array $params
     */
    public static function applyTenantAndObjectOwnerCondition(QueryBuilder $qb, UserIdentity $user, string $currentRole, array $params): void
    {
        $qb->join(PropertyUser::class, 'pu', 'WITH', 'a.identifier = pu.object')
            ->join('pu.role', 'r')
            ->join('pu.user', 'ad')
            ->join('pu.contract', 'cn')
            ->where('((d.damageOwner = :user and d.allocation = 0) OR (d.user = :user)) and r.roleKey = :role and pu.deleted = :deleted and pu.isActive = :active and cn.active = :contractStatus')
            ->setParameter('user', $user->getIdentifier())
            ->setParameter('deleted', false)
            ->setParameter('role', $currentRole)
            ->setParameter('active', true)
            ->setParameter('contractStatus', true);
    }
}