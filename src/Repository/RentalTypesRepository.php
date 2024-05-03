<?php

namespace App\Repository;

use App\Entity\RentalTypes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RentalTypes|null find($id, $lockMode = null, $lockVersion = null)
 * @method RentalTypes|null findOneBy(array $criteria, array $orderBy = null)
 * @method RentalTypes[]    findAll()
 * @method RentalTypes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RentalTypesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RentalTypes::class);
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
        $query = $qb->select("c.publicId", $name, "c.nameEn AS type")
            ->where('c.deleted = 0');
        return $query->getQuery()->getResult();
    }
}
