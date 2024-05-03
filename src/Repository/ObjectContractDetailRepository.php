<?php

namespace App\Repository;

use App\Entity\ObjectContractDetail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Apartment;
use App\Entity\ObjectContracts;
use App\Entity\ContractTypes;

class ObjectContractDetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ObjectContractDetail::class);
    }

    /**
     *
     * @param Apartment $apartment
     * @return void
     */
    public function removeObjectDetails(Apartment $apartment): void
    {
        $this->createQueryBuilder('o')
            ->update()
            ->set('o.deleted', ':status')
            ->set('o.active', ':status')
            ->where('o.object = :object')
            ->setParameter('status', 1)
            ->setParameter('object', $apartment)
            ->getQuery()
            ->execute();
    }

    /**
     *
     * @param Apartment $apartment
     * @param string $locale
     * @return array
     */
    public function findActiveContractType(Apartment $apartment, string $locale): array
    {
        $qb = $this->createQueryBuilder('o');
        return $qb->select("c.name$locale as name")
            ->leftJoin(ContractTypes::class, 'c', 'WITH', 'o.contractType = c.identifier')
            ->leftJoin(ObjectContracts::class, 'oc', 'WITH', 'oc.object = o.identifier')
            ->where('o.object = :object')
            ->andWhere('o.deleted = :deleted')
//                ->andWhere('o.active = :active')
            ->setParameters(['object' => $apartment, 'deleted' => 0])
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
    }
}
