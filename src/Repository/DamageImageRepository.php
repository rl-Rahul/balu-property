<?php

namespace App\Repository;

use App\Entity\DamageImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Filesystem\Filesystem;
use App\Entity\Folder;
use App\Entity\Property;
use App\Entity\Apartment;
use App\Entity\Damage;

/**
 * @method DamageImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method DamageImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method DamageImage[]    findAll()
 * @method DamageImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DamageImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DamageImage::class);
    }

    /* @param DamageImage $document
     * @return int|mixed|string
     * @throws
     */
    public function delete(DamageImage $document)
    {
        $fs = new Filesystem();
        if ($fs->exists($document->getPath())) {
            $fs->remove($document->getPath());
        }
        $pathParts = pathinfo($document->getPath());
        $compressedFile = $pathParts['dirname'] . '/' . $pathParts['filename'] . '.zip';
        if ($fs->exists($compressedFile)) {
            $fs->remove($compressedFile);
        }
        $qb = $this->createQueryBuilder('d');
        return $qb
            ->update()
            ->set('d.deleted', ":deleted")
            ->where('d.identifier = :identifier')
            ->setParameters(['identifier' => $document->getIdentifier(), 'deleted' => true])
            ->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array $users
     * @param int|null $folder
     * @param bool $isRestricted
     * @param array $options
     * @return array
     */
    public function getDocuments(array $users, bool $isRestricted = false, ?int $folder = null, array $options = []): array
    {
        $param = ['deleted' => false];
        $toSelect = ["d.publicId", "da.identifier as damageId", "d.path", "d.name as displayName", "d.name as originalName",
            "CASE 
            WHEN d.imageCategory = 1 THEN 'photos'  
            WHEN d.imageCategory = 2 THEN 'floor_plan' 
            WHEN d.imageCategory = 3 THEN 'bar_code'  
            WHEN d.imageCategory = 4 THEN 'offer_doc' 
            WHEN d.imageCategory = 5 THEN 'defect'  
            WHEN d.imageCategory = 6 THEN 'confirm'  
            ELSE '' END as type",
            "d.path as storedPath", "'public' as isPrivate", "d.mimeType", "d.fileSize as size", "f.publicId AS folder",
            "a.publicId AS apartment", "0 as isEditable"
        ];
        $qb = $this->createQueryBuilder('d')
            ->select($toSelect)
            ->leftJoin(Folder::class, 'f', 'WITH', 'f.identifier = d.folder')
            ->leftJoin(Damage::class, 'da', 'WITH', 'da.identifier = d.damage')
            ->leftJoin(Apartment::class, 'a', 'WITH', 'a.identifier = da.apartment')
            ->andWhere('d.deleted = :deleted AND f.deleted = :deleted');
//        $param['users'] = $users;
        if ($isRestricted) {
            $qb->andWhere('f.isPrivate = :isPrivate');
            $param['isPrivate'] = false;
        }
        if (array_key_exists('search', $options) && !empty($options['search'])) {
            $qb->andWhere('d.name LIKE :displayName');
            $param['displayName'] = '%' . $options['search'] . '%';
        }
        $qb->andWhere('d.folder = :folder');
        $param['folder'] = $folder;

        $qb->setParameters($param)
            ->orderBy('d.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     *
     * @param \DateTime $curDate
     * @return array
     */
    public function getDocumentsByDate(\DateTime $curDate): array
    {
        $query = $this->createQueryBuilder('d')
            ->where('d.createdAt < :curDate')
            ->andWhere('d.damage IS NULL')
            ->setParameter('curDate', $curDate);

        return $query->getQuery()->getResult();
    }

    /**
     *
     * @param array $ids
     * @return void
     */
    public function deleteDamageImages(array $ids): void
    {
        $qb = $this->createQueryBuilder('di');
        $query = $qb->update('App\Entity\DamageImage', 'di')
            ->set('di.deleted', ':deleted')
            ->where('di.damage in (:ids)')
            ->setParameters(array('deleted' => true, 'ids' => $ids))
            ->getQuery();
        $query->execute();
    }
}
