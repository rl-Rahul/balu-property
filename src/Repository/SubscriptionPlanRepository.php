<?php

namespace App\Repository;

use App\Entity\SubscriptionPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SubscriptionPlan|null find($id, $lockMode = null, $lockVersion = null)
 * @method SubscriptionPlan|null findOneBy(array $criteria, array $orderBy = null)
 * @method SubscriptionPlan[]    findAll()
 * @method SubscriptionPlan[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubscriptionPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionPlan::class);
    }

    /**
     * get basic subscription plan based on minimum apartment
     *
     * @param string $period
     * @return SubscriptionPlan|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getBasicSubscriptionPlan(string $period): ?SubscriptionPlan
    {
        $period = ($period === 30) ? 1 : 2;
        $em = $this->getEntityManager();
        $min = $em->createQuery('SELECT MIN(m.apartmentMin) FROM App:SubscriptionPlan m')->getSingleScalarResult();
        return $this->createQueryBuilder('p')
            ->where('p.active = :active AND p.initialPlan = :initialPlan AND p.apartmentMin = :min AND p.period = :period')
            ->setParameters(['min' => $min, 'initialPlan' => 0, 'active' => true, 'period' => $period])
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get subscription plan based on apartment count
     *
     * @param int $apartmentCount
     * @param int $period
     *
     * @return int|mixed|string|null $plan
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getSubscriptionPlan(int $apartmentCount, int $period): ?SubscriptionPlan
    {
        $period = ($period === 30) ? 1 : 2;
        return $this->createQueryBuilder('p')
            ->where('p.active = :active')
            ->andWhere('p.initialPlan = 0')
            ->andWhere('p.period = :period')
            ->andWhere('p.apartmentMin <= :apartmentCount')
            ->andWhere('p.apartmentMax >= :apartmentCount')
            ->setParameters(['period' => $period, 'apartmentCount' => $apartmentCount, 'active' => true])
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get plans with same apartment count
     *
     * @param SubscriptionPlan $bpSubscriptionPlan
     *
     * @return array
     */
    public function getPlanArray(SubscriptionPlan $bpSubscriptionPlan): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.active = 1')
            ->andWhere('p.initialPlan = 0')
            ->andWhere('p.apartmentMin = :apartmentMin')
            ->andWhere('p.apartmentMax = :apartmentMax')
            ->setParameters(['apartmentMin' => $bpSubscriptionPlan->getApartmentMin(), 'apartmentMax' => $bpSubscriptionPlan->getApartmentMax()])
            ->getQuery()
            ->getResult();
    }

    /**
     *
     * @param int $apartmentCount
     * @return array|null
     */
    public function getSubscriptionPlanByCount(int $apartmentCount): ?array
    {
        return $this->createQueryBuilder('p')
            ->where('p.active = :active')
            ->andWhere('p.initialPlan = 0')
            ->andWhere('p.apartmentMin <= :apartmentCount')
            ->andWhere('p.apartmentMax >= :apartmentCount')
            ->setParameters(['apartmentCount' => $apartmentCount, 'active' => true])
            ->getQuery()
            ->getResult();
    }
}
