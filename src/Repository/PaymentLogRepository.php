<?php

namespace App\Repository;

use App\Entity\PaymentLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PaymentLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method PaymentLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method PaymentLog[]    findAll()
 * @method PaymentLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentLog::class);
    }

}
