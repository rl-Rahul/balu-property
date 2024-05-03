<?php

namespace App\Repository;

use App\Entity\Directory;
use App\Entity\PropertyRoleInvitation;
use App\Entity\PropertyUser;
use App\Entity\UserIdentity;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DirectoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Directory::class);
    }

    /**
     *
     * @param UserIdentity $user
     * @return array
     */
    public function getInvitees(UserIdentity $user): array
    {
        return $this->_em->getRepository(UserIdentity::class)->getUserList($user);
    }

    /**
     * function to check if user have given role. return user if found.
     *
     * @param string $user
     * @param string $role
     * @return Directory|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getUserWithRole(string $user, string $role): ?Directory
    {
        $qb = $this->createQueryBuilder('d')
            ->join('d.user', 'ui')
            ->leftJoin('ui.role', 'r')
            ->andWhere('ui.deleted = :deleted')
            ->andWhere('ui.enabled = :enabled')
            //->andWhere('r.roleKey = :role')
            ->andWhere('d.publicId = :user')
            ->setParameter('user', $user, 'uuid')
            ->setParameter('deleted', false)
            ->setParameter('enabled', true);
        //->setParameter('role', $role); //add this after defining individual role
        return $qb->getQuery()->getOneOrNullResult();
    }
}
