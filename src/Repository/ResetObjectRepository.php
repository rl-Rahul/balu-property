<?php

namespace App\Repository;

use App\Entity\ResetObject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\UserIdentity;
use App\Entity\Apartment;
use App\Entity\Property;


class ResetObjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResetObject::class);
    }

    /**
     *
     * @param array $params
     * @param bool $countOnly
     * @return type
     */
    public function getResetList(array $params, ?bool $countOnly = false, ?Property $property = null)
    {
        $query = $this->createQueryBuilder('r');
        $query->select('r.createdAt, r.publicId, r.reason, p.publicId as productId, r.isSuperAdminApproved, a.identifier, r.superAdminComment, u.firstName, u.lastName, a.name, p.address')
            ->leftJoin(UserIdentity::class, 'u', 'WITH', 'u.identifier = r.requestedBy')
            ->leftJoin(Apartment::class, 'a', 'WITH', 'a.identifier = r.apartment')
            ->leftJoin(Property::class, 'p', 'WITH', 'a.property = p.identifier')
            ->where('r.isSuperAdminApproved = false')
            ->orderBy('r.identifier', 'DESC');

        if (!empty($params['limit'])) {
            $query->setMaxResults($params['limit']);
        }
        if (!empty($params['offset'])) {
            $query->setFirstResult($params['offset']);
        }
        if ($countOnly) {
            $query->select('count(distinct p.identifier) as count');
            return $query->getQuery()->getSingleScalarResult();
        }

        if (!empty($property)) {
            $query->andWhere('r.property = :property')
                ->setParameter('property', $property);
        } else {
            // $query->groupBy('p.identifier, ');
            $query->groupBy('p.identifier, r.createdAt, r.publicId, r.reason, p.publicId, r.isSuperAdminApproved, a.identifier, r.superAdminComment, u.firstName, u.lastName, a.name, p.address');
        }

        return $query->getQuery()->getResult();
    }
}
