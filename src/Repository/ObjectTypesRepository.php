<?php

namespace App\Repository;

use App\Entity\ObjectTypes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 */
class ObjectTypesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ObjectTypes::class);
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
        $name = ($locale === 'de') ? 'c.nameDe AS name' : 'c.name AS name';
        $qb = $this->createQueryBuilder('c');
        if ($adminView) {
            $qb->select("c.publicId", "c.sortOrder", "c.active", $name, "c.name AS nameEn, c.nameDe AS nameDe");
        } else {
            $qb->select("c.publicId", $name);
        }
        $query = $qb->where('c.deleted = :deleted')
            ->andWhere('c.name != :name')
            ->setParameters(['name' => 'General/Environment', 'deleted' => false]);
        if ($adminView && isset($params['searchKey']) && !empty(trim($params['searchKey']))) {
            $qb->andWhere("c.name LIKE :search OR c.nameDe LIKE :search")
                ->setParameter('search', '%' . $params['searchKey'] . '%');
        }

        return $query->orderBy('c.sortOrder', 'ASC')->getQuery()->getResult();
    }
}
