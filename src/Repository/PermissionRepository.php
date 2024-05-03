<?php

namespace App\Repository;

use App\Entity\UserIdentity;
use App\Entity\Permission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Permission|null find($id, $lockMode = null, $lockVersion = null)
 * @method Permission|null findOneBy(array $criteria, array $orderBy = null)
 * @method Permission[]    findAll()
 * @method Permission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Permission::class);
    }

    /**
     * @param UserIdentity $userIdentity
     * @param string $currentRole
     * @return array
     */
    public function checkPermissionsOfCurrentLoggedInUser(UserIdentity $userIdentity, string $currentRole): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.roles', 'r')
            ->join('r.propertyUsers', 'pu')
            ->where('r.roleKey = :role')
            ->andWhere('pu.user = :user')
            ->setParameters(['role' => $currentRole, 'user' => $userIdentity])
            ->getQuery()->getResult();
    }
}
