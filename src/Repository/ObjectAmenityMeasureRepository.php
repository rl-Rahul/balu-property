<?php

namespace App\Repository;

use App\Entity\ObjectAmenityMeasure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Apartment;

/**
 * @method ObjectAmenityMeasure|null find($id, $lockMode = null, $lockVersion = null)
 * @method ObjectAmenityMeasure|null findOneBy(array $criteria, array $orderBy = null)
 * @method ObjectAmenityMeasure[]    findAll()
 * @method ObjectAmenityMeasure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ObjectAmenityMeasureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ObjectAmenityMeasure::class);
    }

    /**
     * @param Apartment $bpApartment
     * @return void
     */
    public function removeAmenityMeasures(Apartment $bpApartment): void
    {
        $this->createQueryBuilder('c')
            ->update()
            ->set('c.deleted', ':status')
            ->where('c.object = :object')
            ->setParameter('status', 1)
            ->setParameter('object', $bpApartment)
            ->getQuery()
            ->execute();
    }
}
