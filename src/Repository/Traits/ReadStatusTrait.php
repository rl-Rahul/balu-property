<?php

namespace App\Repository\Traits;
 
use App\Entity\UserIdentity;

trait ReadStatusTrait
{
    /**
     * getReadStatus
     *
     * Function to get all read status of a user
     *
     * @param UserIdentity $user
     * @param int $identifier
     * @return bool
     *
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getReadStatus(UserIdentity $user, int $identifier): bool
    {
        $qb = $this->createQueryBuilder('m');
        $query = $qb
                ->join('m.messageReadUsers', 'users')
                ->andWhere('users = :userId')
                ->andWhere('m.identifier = :identifier')
                ->setParameter('userId', $user)
                ->setParameter('identifier', $identifier)
                ->getQuery();

        return null !== $query->getOneOrNullResult();
    }

}
