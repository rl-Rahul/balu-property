<?php

namespace App\Repository;

use App\Entity\ApartmentLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ApartmentLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApartmentLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApartmentLog[]    findAll()
 * @method ApartmentLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApartmentLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApartmentLog::class);
    }

}
