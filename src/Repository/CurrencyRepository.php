<?php

namespace App\Repository;

use App\Entity\Currency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Currency|null find($id, $lockMode = null, $lockVersion = null)
 * @method Currency|null findOneBy(array $criteria, array $orderBy = null)
 * @method Currency[]    findAll()
 * @method Currency[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CurrencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Currency::class);
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
        $query = $qb->select("c.publicId", $name)
            ->where('c.deleted = 0');
        return $query->getQuery()->getResult();
    }
}
