<?php

namespace App\Repository;

use App\Entity\Floor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FloorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Floor::class);
    }

    /**
     *
     * @param string $locale
     * @param bool $adminView
     * @param array $params
     * @return array
     */
    public function findAllBy(string $locale, $adminView = false, array $params = []): array
    {
        $qb = $this->createQueryBuilder('c');
        if ($adminView) {
            $qb->select("c.publicId, c.floorNumber as name, c.sortOrder");
        } else {
            $qb->select("c.publicId, c.floorNumber, c.sortOrder");
        }
        $query = $qb->where('c.deleted = :deleted')
            ->setParameter('deleted', false);
        if ($adminView && isset($params['searchKey']) && !empty(trim($params['searchKey']))) {
            $qb->andWhere("c.floorNumber LIKE :search")
                ->setParameter('search', '%' . $params['searchKey'] . '%');
        }

        return $query->orderBy('c.sortOrder', 'ASC')->getQuery()->getResult();
    }
}
