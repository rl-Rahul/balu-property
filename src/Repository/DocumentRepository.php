<?php

namespace App\Repository;

use App\Entity\Apartment;
use App\Entity\Document;
use App\Entity\Folder;
use App\Entity\Property;
use App\Service\DMSService;
use App\Utils\Constants;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use App\Entity\ObjectContracts;
use App\Entity\PropertyUser;
use App\Entity\Role;

/**
 * @method Document|null find($id, $lockMode = null, $lockVersion = null)
 * @method Document|null findOneBy(array $criteria, array $orderBy = null)
 * @method Document[]    findAll()
 * @method Document[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentRepository extends ServiceEntityRepository
{
    /**
     * @var ManagerRegistry $registry
     */
    private ManagerRegistry $registry;

    /**
     * @var DMSService $dmsService
     */
    private DMSService $dmsService;

    /**
     * DocumentRepository constructor.
     * @param ManagerRegistry $registry
     * @param DMSService $dmsService
     */
    public function __construct(ManagerRegistry $registry, DMSService $dmsService)
    {
        $this->registry = $registry;
        $this->dmsService = $dmsService;
        parent::__construct($registry, Document::class);
    }

    /**
     * @param Document $document
     * @return int|mixed|string
     * @throws
     */
    public function delete(Document $document)
    {
        $fs = new Filesystem();
        if ($fs->exists($document->getStoredPath())) {
            $fs->remove($document->getStoredPath());
        }
        $pathParts = pathinfo($document->getStoredPath());
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
     * @param int $user
     * @param array $users
     * @param bool $isRestricted
     * @param int|null $folder
     * @param array $options
     * @param Role|null $role
     * @return array
     */
    public function getDocuments(int $user, array $users, bool $isRestricted = false, ?int $folder = null, array $options = [], ?Role $role = null): array
    {
        $param = ['deleted' => false, 'folder' => $folder];
        $toSelect = ["d.publicId", "d.path", "d.displayName", "d.originalName", "d.type", "d.storedPath",
            "CASE WHEN d.isPrivate <> 0 THEN 'private' ELSE 'public' END as isPrivate", "d.mimeType", "d.size", "f.publicId AS folder",
            "p.publicId AS property", "a.publicId AS apartment", "oc.publicId as contract, p.active as isPropertyActive", $this->canEdit($isRestricted)
        ];
        $qb = $this->createQueryBuilder('d')
            ->select($toSelect)
            ->leftJoin(Folder::class, 'f', 'WITH', 'f.identifier = d.folder')
            ->leftJoin(Property::class, 'p', 'WITH', 'p.identifier = d.property')
            ->leftJoin(Apartment::class, 'a', 'WITH', 'a.identifier = d.apartment')
            ->leftJoin(ObjectContracts::class, 'oc', 'WITH', 'oc.identifier = d.contract');
        if (in_array($this->dmsService->convertSnakeCaseString($role->getRoleKey()), [Constants::OBJECT_OWNER_ROLE, Constants::TENANT_ROLE])) {
            $qb->andWhere('d.user IN (:users) AND d.isPrivate = :isPrivate AND f.deleted = :deleted AND (a.active IS NULL OR a.active = :isActive)');
            $param += ['users' => array_unique($users), 'isPrivate' => false, 'isActive' => true];
            $param += ['editableUsers' => []];
        } else if ($this->dmsService->convertSnakeCaseString($role->getRoleKey()) === Constants::JANITOR_ROLE) {
            $param += ['editableUsers' => [$user]];
            $qb->andWhere('d.isPrivate = :isPrivate AND f.deleted = :deleted');
            $param += ['isPrivate' => false];
        } else {
            $param += ['editableUsers' => [$user]];
//            $qb->andWhere('(d.isPrivate = :isPrivate AND d.user = :currentUser) OR d.isPrivate = false');
//            $param += ['isPrivate' => true, 'currentUser' => $user];
            $qb->andWhere('d.deleted = :deleted AND f.deleted = :deleted');
        }
        if (array_key_exists('search', $options) && !empty($options['search'])) {
            $qb->andWhere('d.displayName LIKE :displayName');
            $param['displayName'] = '%' . $options['search'] . '%';
        }
        $qb->andWhere('d.folder = :folder AND d.deleted = :deleted');
        $qb->setParameters($param)
            ->orderBy('d.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param bool $isRestricted
     * @return string
     */
    public function canEdit(bool $isRestricted = false): string
    {
        return "CASE WHEN (d.user IN (:editableUsers)  OR " . ($isRestricted ? 'false' : 'true') . " = true) THEN true ELSE false END as isEditable";
    }

}
