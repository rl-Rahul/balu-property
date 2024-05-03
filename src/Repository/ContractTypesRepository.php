<?php

namespace App\Repository;

use App\Entity\ContractTypes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 */
class ContractTypesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContractTypes::class);
    }

    /**
     *
     * @param string $locale
     * @return array
     */
    public function findAllBy(string $locale): array
    {
        $name = ($locale === 'de') ? 'c.nameDe AS name' : 'c.nameEn AS name';
        $qb = $this->createQueryBuilder('c');
        $query = $qb->select("c.publicId", $name, "LOWER(c.nameEn) AS type")
            ->where('c.deleted = 0');
        return $query->getQuery()->getResult();
    }
}
