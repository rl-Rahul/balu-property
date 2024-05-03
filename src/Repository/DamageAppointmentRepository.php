<?php

namespace App\Repository;

use App\Entity\Damage;
use App\Entity\DamageImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Filesystem\Filesystem;
use App\Entity\Folder;
use App\Entity\Property;
use App\Entity\Apartment;
use App\Entity\DamageAppointment;

/**
 * @method DamageImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method DamageImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method DamageImage[]    findAll()
 * @method DamageImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DamageAppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DamageAppointment::class);
    }

    /**
     *
     * @param array $ids
     * @return void
     */
    public function deleteDamageAppointments(array $ids): void
    {
        $qb = $this->createQueryBuilder('da');
        $query = $qb->update('App\Entity\DamageAppointment', 'da')
            ->set('da.deleted', ':deleted')
            ->where('da.damage in (:ids)')
            ->setParameters(array('deleted' => true, 'ids' => $ids))
            ->getQuery();
        $query->execute();
    }

    /**
     * @param Damage $damage
     * @return array|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLatestAppointmentDate(Damage $damage): ?array
    {
        return $this->createQueryBuilder('a')
            ->select('a.scheduledTime')
            ->where('a.damage = :damage AND a.deleted = :deleted')
            ->setParameters(['damage' => $damage, 'deleted' => false])
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }
}
