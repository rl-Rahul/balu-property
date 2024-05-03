<?php

namespace App\Repository;

use App\Entity\ReferenceIndex;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReferenceIndexRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReferenceIndex::class);
    }

    /**
     *
     * @param string $locale
     * @param bool $adminView
     * @param array $params
     * @return array
     */
    public function findAllBy(string $locale, bool $adminView = false, array $params = []): array
    {
        $qb = $this->createQueryBuilder('c');
        if ($adminView) {
            $qb->select("c.publicId, c.sortOrder, c.active, c.name");
        } else {
            $qb->select("c.publicId, c.name");
        }
        $query = $qb->where('c.deleted = 0');
        if ($adminView && isset($params['searchKey']) && !empty(trim($params['searchKey']))) {
            $qb->andWhere("c.name LIKE :search")
                ->setParameter('search', '%' . $params['searchKey'] . '%');
        }

        return $query->orderBy('c.sortOrder', 'ASC')->getQuery()->getResult();
    }
}
