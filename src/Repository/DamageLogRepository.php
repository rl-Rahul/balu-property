<?php

namespace App\Repository;

use App\Entity\DamageImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Filesystem\Filesystem;
use App\Entity\Folder;
use App\Entity\Property;
use App\Entity\Apartment;
use App\Entity\DamageLog;

/**
 * @method DamageImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method DamageImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method DamageImage[]    findAll()
 * @method DamageImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DamageLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DamageLog::class);
    }

    /**
     *
     * @param array $ids
     * @return void
     */
    public function deleteLogs(array $ids): void
    {
        $qb = $this->createQueryBuilder('da');
        $query = $qb->update('App\Entity\DamageLog', 'da')
            ->set('da.deleted', ':deleted')
            ->where('da.damage in (:ids)')
            ->setParameters(array('deleted' => true, 'ids' => $ids))
            ->getQuery();
        $query->execute();
    }

    /**
     * get damage log details
     *
     * @param int $damage
     * @return array
     */
    public function getDamageLogDetails(int $damage): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.publicId, s.status AS description, s.key AS status, l.createdAt, l.statusText, l.responsibles, IDENTITY(l.preferredCompany) as preferredCompany')
            ->join('l.status', 's')
            ->where('l.damage = :damage AND l.deleted = :deleted AND s.deleted = :deleted')
            ->setParameters(['damage' => $damage, 'deleted' => 0])
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()->getArrayResult();
    }
}
