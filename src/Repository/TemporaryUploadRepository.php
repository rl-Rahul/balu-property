<?php

namespace App\Repository;

use App\Entity\TemporaryUpload;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @method TemporaryUpload|null find($id, $lockMode = null, $lockVersion = null)
 * @method TemporaryUpload|null findOneBy(array $criteria, array $orderBy = null)
 * @method TemporaryUpload[]    findAll()
 * @method TemporaryUpload[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TemporaryUploadRepository extends ServiceEntityRepository
{
    /**
     * TemporaryUploadRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TemporaryUpload::class);
    }

    /**
     * @param TemporaryUpload $temporaryUpload
     * @return int|mixed|string
     */
    public function delete(TemporaryUpload $temporaryUpload)
    {
        $fs = new Filesystem();
        if ($fs->exists($temporaryUpload->getTemporaryUploadPath())) {
            $fs->remove($temporaryUpload->getTemporaryUploadPath());
        }
        $qb = $this->createQueryBuilder('t');
        return $qb
            ->delete()
            ->where('t.identifier = :identifier')
            ->setParameter('identifier', $temporaryUpload->getIdentifier())
            ->getQuery()->execute();
    }
}
