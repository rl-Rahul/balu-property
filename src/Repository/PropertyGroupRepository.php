<?php

namespace App\Repository;

use App\Entity\PropertyGroup;
use App\Entity\Property;
use App\Entity\UserIdentity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PropertyGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method PropertyGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method PropertyGroup[]    findAll()
 * @method PropertyGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PropertyGroupRepository extends ServiceEntityRepository
{
    /**
     * PropertyGroupRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyGroup::class);
    }

    /**
     * @param Property $property
     * @return PropertyGroup[]|Collection|string
     */
    public function getActiveGroup(Property $property)
    {
        return (empty($property->getPropertyGroups())) ? $property->getPropertyGroups() : 'Ungrouped';
    }

    /**
     * @param UserIdentity $user
     * @return array
     */
    public function getGroupName(UserIdentity $user): array
    {
        $qb = $this->createQueryBuilder('pg')
            ->select('pg.name')
            ->where('pg.createdBy = :createdBy')
            ->andWhere('pg.deleted = :deleted')
            ->setParameters(['createdBy' => $user, 'deleted' => false]);
        return array_column($qb->getQuery()->getResult(), "name");
    }
}
