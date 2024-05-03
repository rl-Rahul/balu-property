<?php

namespace App\Repository;

use App\Entity\ModeOfPayment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ModeOfPayment|null find($id, $lockMode = null, $lockVersion = null)
 * @method ModeOfPayment|null findOneBy(array $criteria, array $orderBy = null)
 * @method ModeOfPayment[]    findAll()
 * @method ModeOfPayment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModeOfPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModeOfPayment::class);
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
