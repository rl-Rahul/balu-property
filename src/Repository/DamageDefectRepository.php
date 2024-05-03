<?php

namespace App\Repository;

use App\Entity\DamageImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Filesystem\Filesystem;
use App\Entity\Folder;
use App\Entity\Property;
use App\Entity\Apartment;
use App\Entity\DamageDefect;

/**
 * @method DamageImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method DamageImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method DamageImage[]    findAll()
 * @method DamageImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DamageDefectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DamageDefect::class);
    }

    /**
     *
     * @param array $ids
     * @return void
     */
    public function deleteDefects(array $ids): void
    {
        $qb = $this->createQueryBuilder('dd');
        $query = $qb->update('App\Entity\DamageDefect', 'dd')
            ->set('dd.deleted', ':deleted')
            ->where('dd.damage in (:ids)')
            ->setParameters(array('deleted' => true, 'ids' => $ids))
            ->getQuery();
        $query->execute();
    }
}
