<?php

namespace App\Repository;

use App\Entity\UserPropertyPool;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use OpenApi\LinkExample\User;

/**
 * @method UserPropertyPool|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserPropertyPool|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserPropertyPool[]    findAll()
 * @method UserPropertyPool[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserPropertyPoolRepository extends ServiceEntityRepository
{
    /**
     * UserPropertyPoolRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPropertyPool::class);
    }

}
