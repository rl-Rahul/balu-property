<?php

namespace App\Repository;

use App\Entity\Damage;
use App\Entity\DamageImage;
use App\Entity\UserIdentity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Filesystem\Filesystem;
use App\Entity\Folder;
use App\Entity\Property;
use App\Entity\Apartment;
use App\Entity\DamageOffer;

/**
 * @method DamageImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method DamageImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method DamageImage[]    findAll()
 * @method DamageImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DamageOfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DamageOffer::class);
    }

    /**
     *
     * @param array $ids
     * @return void
     */
    public function deleteDamageOffers(array $ids): void
    {
        $qb = $this->createQueryBuilder('d');
        $query = $qb->update('App\Entity\DamageOffer', 'do')
            ->set('do.deleted', ':deleted')
            ->where('do.damage in (:ids)')
            ->setParameters(array('deleted' => true, 'ids' => $ids))
            ->getQuery();
        $query->execute();
    }

    /**
     * @param Damage $damage
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getOfferCompanyName(Damage $damage): ?array
    {
        return $this->createQueryBuilder('o')
            ->select('c.firstName, c.lastName, c.companyName, c.identifier')
            ->where('o.damage = :damage AND o.acceptedDate IS NULL AND o.deleted = :deleted')
            ->join('o.company', 'c')
            ->setParameters(['damage' => $damage, 'deleted' => false])
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @param UserIdentity $company
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getOfferPrice(UserIdentity $company): ?array
    {
        return $this->createQueryBuilder('o')
            ->select('o.amount, o.priceSplit, IDENTITY(o.company) as company')
            ->where('o.company = :company AND o.deleted = :deleted')
            ->setParameters(['company' => $company, 'deleted' => false])
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Damage $damage
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findCompanyWithOffer(Damage $damage)
    {
        return $this->createQueryBuilder('o')
            ->select('IDENTITY(o.company) as company')
            ->where('o.damage = :damage AND o.deleted = :deleted AND o.acceptedDate IS NOT NULL')
            ->setParameters(['damage' => $damage, 'deleted' => false])
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }
}
