<?php

namespace App\Repository;

use App\Entity\NoticePeriod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NoticePeriod|null find($id, $lockMode = null, $lockVersion = null)
 * @method NoticePeriod|null findOneBy(array $criteria, array $orderBy = null)
 * @method NoticePeriod[]    findAll()
 * @method NoticePeriod[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NoticePeriodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NoticePeriod::class);
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
