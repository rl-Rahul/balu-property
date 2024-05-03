<?php

namespace App\Repository;

use App\Entity\Apartment;
use App\Entity\Document;
use App\Entity\Property;
use App\Entity\User;
use App\Entity\UserIdentity;
use App\Entity\Folder;
use App\Service\DMSService;
use App\Utils\Constants;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\DBAL\Exception\NonUniqueFieldNameException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use App\Entity\ObjectContracts;
use App\Entity\DamageImage;
use App\Entity\PropertyUser;
use App\Entity\Role;
use App\Entity\Damage;

/**
 * @method Folder|null find($id, $lockMode = null, $lockVersion = null)
 * @method Folder|null findOneBy(array $criteria, array $orderBy = null)
 * @method Folder[]    findAll()
 * @method Folder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FolderRepository extends ServiceEntityRepository
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
     * FolderRepository constructor.
     * @param ManagerRegistry $registry
     * @param DMSService $dmsService
     */
    public function __construct(ManagerRegistry $registry, DMSService $dmsService)
    {
        $this->registry = $registry;
        $this->dmsService = $dmsService;
        parent::__construct($registry, Folder::class);
    }

    /**
     * @param int $user
     * @param array $users
     * @param string|null $parent
     * @param bool $isRestricted
     * @param array $options
     * @param Role|null $role
     * @return array
     * @throws InvalidArgumentException
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getFolders(int $user, array $users, ?string $parent = null, bool $isRestricted = false, array $options = [], ?Role $role = null): array
    {
        $roleKey = $this->dmsService->convertSnakeCaseString($role->getRoleKey());
        switch ($roleKey) {
            case ($roleKey === Constants::TENANT_ROLE || $roleKey === Constants::OBJECT_OWNER_ROLE):
                $result = $this->getObjectLevelUserFolders($user, $users, $isRestricted, $role, $parent, $options);
                break;
            case ($roleKey === Constants::OWNER_ROLE || $roleKey === Constants::PROPERTY_ADMIN_ROLE || $roleKey === Constants::JANITOR_ROLE):
                $result = $this->getPropertyLevelUserFolders($user, $isRestricted, $role, $parent, $options);
                break;
            case ($roleKey === Constants::COMPANY_ROLE):
                $result = [];
                break;
            default:
                throw new InvalidArgumentException('invalidRole');
        }
        return $result;
    }

    /**
     * @param Folder|null $folder
     * @return array|null
     */
    public function getFolderInfo(?Folder $folder = null): ?array
    {
        if (is_null($folder)) {
            return null;
        }
        return $this->createQueryBuilder('f')
            ->select('f.identifier, f.publicId, f.name, f.path, f.displayName, f.isPrivate, f.isSystemGenerated')
            ->where('f.identifier = :folder')
            ->andWhere('f.deleted = :deleted')
            ->setParameters(['folder' => $folder, 'deleted' => false])
            ->getQuery()->getResult();
    }

    /**
     * @param string $folderName
     * @param UserIdentity $user
     * @return int|null
     * @throws \Doctrine\ORM\NonUniqueResultException|\Doctrine\ORM\NoResultException
     */
    public function checkIsFolderNameExists(string $folderName, UserIdentity $user): ?int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.identifier)')
            ->where('f.createdBy = :user AND f.displayName = :displayName AND f.deleted = :deleted')
            ->setParameters(['user' => $user, 'deleted' => false, 'displayName' => $folderName])
            ->getQuery()->getSingleScalarResult();
    }

    /**
     * @param string $parent
     * @param string $locale
     * @return array
     */
    public function getParentFolders(string $parent, $locale = 'en'): array
    {
        $query = $this->createQueryBuilder('f')
            ->select('f.identifier, IDENTITY(f.parent) AS parentId, f.publicId, f.name, f.path, f.displayName,
              f.isPrivate, f.isSystemGenerated, p.publicId AS parent, pr.publicId AS property, a.publicId AS apartment, oc.publicId AS contract');
        if ($locale == 'de') {
            $query->addSelect('CASE WHEN f.displayName = :systemGeneratedEn THEN :systemGeneratedDe ELSE f.displayName END AS displayName')
                ->setParameter('systemGeneratedEn', Constants::SYSTEM_GENERATED_OBJECT)
                ->setParameter('systemGeneratedDe', Constants::SYSTEM_GENERATED_OBJECT_DE);
        }
        $query->leftJoin(Folder::class, 'p', 'WITH', 'p.identifier = f.parent')
            ->leftJoin(Property::class, 'pr', 'WITH', 'pr.folder = p.identifier OR pr.folder = f.identifier')
            ->leftJoin(Apartment::class, 'a', 'WITH', 'a.folder = f.identifier')
            ->leftJoin(ObjectContracts::class, 'oc', 'WITH', 'oc.folder = f.identifier')
            ->where('f.publicId = :publicId AND f.deleted = :deleted')
            ->setParameter('publicId', $parent, 'uuid')
            ->setParameter('deleted', false);
        $data = $query->getQuery()->getResult();
        $data = reset($data);
        $data['type'] = $this->getResourceType($data);
        if (!empty($data) && isset($data['parentId']) && isset($data['parent'])) {
            $data[] = $this->getParentFolders($data['parent']);
        }
        return $data;
    }

    /**
     * @param array $folders
     * @param array|null $properties
     * @param array|null $users
     * @param int|null $user
     * @param bool $isRestricted
     * @param Role $role
     * @param array $options
     * @return array
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getCountAndType(array $folders, ?array $properties, ?array $users, ?int $user, bool $isRestricted, Role $role, array $options = []): array
    {
        $data = array();
        foreach ($folders as $key => $folder) {
            if ($isRestricted) {
                $fWhere = 'f.parent = :folder AND f.deleted = :deleted AND f.isPrivate = :isPrivate';
                $dWhere = 'd.folder = :folder AND d.deleted = :deleted AND f.deleted = :deleted AND d.isPrivate = :isPrivate';
                $parameters = ['deleted' => false, 'isPrivate' => false, 'folder' => $folder['identifier']];
                $damageImagesWhere = 'd.folder = :folder AND d.deleted = :deleted AND f.deleted = :deleted';
                $damageImagesParameters = ['deleted' => false, 'folder' => $folder['identifier']];
            } else {
                $fWhere = 'f.parent = :folder AND f.deleted = :deleted';
                $dWhere = 'd.folder = :folder AND d.deleted = :deleted AND f.deleted = :deleted';
                $parameters = ['deleted' => false, 'folder' => $folder['identifier']];
                $damageImagesWhere = $dWhere;
                $damageImagesParameters = $parameters;
            }
            $function = debug_backtrace()[1]['function'];
            if ($function === 'getPropertyRoleBasedFolders') {
                $folderCount = $this->$function($user, $isRestricted, $role, $folder['publicId'], $options, true);
            } elseif ($function === 'getPropertyAndObjectFolders') {
                $folderObj = $this->registry->getRepository(Folder::class)->findOneBy(['publicId' => $folder['publicId']]);
                $folderCount = $this->$function($folderObj, $isRestricted, $role, true);
            } else {
                $folderCount = $this->$function($properties, $users, $user, $isRestricted, $role, (string)$folder['publicId'], $options, true);
            }
            $folder['folderCount'] = isset($folderCount[0]) ? $folderCount[0]['count'] : 0;
            $damageImageCount = (int)$this->createQueryBuilder('f')
                ->select('COUNT(d.identifier) AS documentCount')
                ->leftJoin(DamageImage::class, 'd', 'WITH', 'd.folder = f.identifier')
                ->where($damageImagesWhere)
                ->setParameters($damageImagesParameters)
                ->getQuery()->getSingleScalarResult();

            $folder['documentCount'] = (int)$this->createQueryBuilder('f')
                    ->select('COUNT(d.identifier) AS documentCount')
                    ->leftJoin(Document::class, 'd', 'WITH', 'd.folder = f.identifier')
                    ->where($dWhere)
                    ->setParameters($parameters)
                    ->getQuery()->getSingleScalarResult() + $damageImageCount;
            $folder['type'] = $this->getResourceType($folder);
            $folder['isPrivate'] = $folder['isPrivate'] === true ? 'private' : 'public';
            $data[] = $folder;
        }
        return $data;
    }

    /**
     * @param array $folder
     * @return string
     */
    private function getResourceType(array $folder): string
    {
        $type = '';
        if (!is_null($folder['property'])) {
            $type = 'property';
        }
        if (isset($folder['apartment']) && !is_null($folder['apartment'])) {
            $type = 'apartment';
        }
        if (isset($folder['contract']) && !is_null($folder['contract'])) {
            $type = 'contract';
        }
        if (isset($folder['isSystemGenerated']) && false !== strpos($folder['displayName'], 'damage')) {
            $type = 'damage';
        }


        return $type;
    }

    /**
     * @param Folder $folder
     * @return int|mixed|string
     */
    public function delete(Folder $folder)
    {
        $fs = new Filesystem();
        if (!$fs->exists($folder->getPath())) {
            throw new FileNotFoundException('fileNotFound');
        }
//        $fs->remove($folder->getPath());
        $qb = $this->createQueryBuilder('f');
        return $qb
            ->update()
            ->set('f.deleted', ":deleted")
            ->where('f.identifier = :identifier')
            ->setParameters(['identifier' => $folder->getIdentifier(), 'deleted' => true])
            ->getQuery()->execute();
    }

    /**
     * @param Folder $folder
     * @return int|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function checkFolderCount(Folder $folder): ?int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.identifier)')
            ->where('f.parent = :parent AND f.deleted = :deleted')
            ->setParameters(['deleted' => false, 'parent' => $folder])
            ->getQuery()->getSingleScalarResult();
    }

    /**
     *
     * @param Folder $parent
     * @return array
     */
    public function findParent(Folder $parent): array
    {
        $folderArray = [];
        $query = $this->createQueryBuilder('f')
            ->select('IDENTITY(f.parent) as parentId')
            ->where('f.publicId = :publicId AND f.deleted = :deleted')
            ->setParameter('publicId', $parent)
            ->setParameter('deleted', false);
        $data = $query->getQuery()->getResult();
        $data = reset($data);
        if (!empty($data) && isset($data['parentId'])) {
            $folderArray[] = $this->findParent($data['parent']);
        }
        return $folderArray;
    }

    /**
     * @param bool $isRestricted
     * @return string
     */
    public function canEdit(bool $isRestricted = false): string
    {
        return "CASE WHEN (f.isSystemGenerated = FALSE AND (f.createdBy IN (:editableUsers) OR " . ($isRestricted ? 'false' : 'true') . " = true)) THEN true ELSE false END as isEditable";
    }

    /**
     * @param int $user
     * @param array $users
     * @param bool $isRestricted
     * @param Role $role
     * @param string|null $parent
     * @param array $options
     * @return array
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getObjectLevelUserFolders(int $user, array $users, bool $isRestricted, Role $role, ?string $parent = null, array $options = []): array
    {
        if (isset($options['properties']) && !empty($options['properties'])) {
            $properties = $options['properties'];
        } else {
            $properties = $this->getPropertiesOfUsers($users, $role);
        }
        return $this->getObjectRoleBasedFolders($properties, $users, $user, $isRestricted, $role, $parent, $options);
    }

    /**
     * @param array $users
     * @param Role $role
     * @return array
     */
    private function getPropertiesOfUsers(array $users, Role $role): array
    {
        $params = ['deleted' => false, 'users' => $users];
        $qb = $this->createQueryBuilder('f')
            ->select('DISTINCT p.identifier')
            ->leftJoin(Folder::class, 'f1', 'WITH', 'f.parent = f1.identifier')
            ->leftJoin(Property::class, 'p', 'WITH', 'p.folder = f.identifier OR p.folder = f1.identifier')
            ->leftJoin(Apartment::class, 'a', 'WITH', 'a.folder = f.identifier AND a.property = p.identifier')
            ->leftJoin(ObjectContracts::class, 'o', 'WITH', 'o.object = a.identifier')
            ->leftJoin(PropertyUser::class, 'pu', 'WITH', 'pu.object = a.identifier AND pu.contract = o.identifier')
            ->leftJoin(Role::class, 'r', 'WITH', 'r.identifier = pu.role')
            ->where('pu.user IN (:users)');
        if (!in_array($this->dmsService->convertSnakeCaseString($role->getRoleKey()), [Constants::OWNER_ROLE, Constants::PROPERTY_ADMIN_ROLE])) {
            $qb->andWhere('pu.isActive = :isActive AND r.identifier = :role AND f.isPrivate = :isPrivate AND pu.deleted = :deleted 
                AND pu.isActive = :isActive AND r.identifier = :role AND f.isPrivate = :isPrivate AND pu.deleted = :deleted AND f.deleted = :deleted AND f1.deleted = :deleted');
            $params += ['isActive' => true, 'isPrivate' => false, 'role' => $role->getIdentifier()];
        } else if ($this->dmsService->convertSnakeCaseString($role->getRoleKey()) === Constants::OWNER_ROLE) {
            $qb->orWhere('p.user IN (:users) AND p.deleted = :deleted');
        } else if ($this->dmsService->convertSnakeCaseString($role->getRoleKey()) === Constants::PROPERTY_ADMIN_ROLE) {
            $qb->orWhere('p.administrator IN (:users) AND p.deleted = :deleted');
        } else {
            // TODO
        }
        $qb->groupBy('p.identifier, f.identifier')
            ->setParameters($params);
        return array_column($qb->getQuery()->getResult(), 'identifier');
    }

    /**
     * @param array $properties
     * @param array $users
     * @param int $user
     * @param bool $isRestricted
     * @param Role $role
     * @param string|null $parent
     * @param array $options
     * @param bool $countOnly
     * @return array
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getObjectRoleBasedFolders(array $properties, array $users, int $user, bool $isRestricted, Role $role, ?string $parent = null, array $options = [], bool $countOnly = false): array
    {

        $param = ['deleted' => false];
        $toSelect = $countOnly ?
            ['count(distinct f.identifier) as count'] :
            ['f.identifier, f.publicId, f.name, f.path, f.displayName, f.isPrivate, f.isSystemGenerated, pr.publicId AS property, pr.identifier AS propertyId,
             COALESCE(pr.active, true) AS isPropertyActive, a.active as isApartmentActive ', $this->canEdit(true)];
        if (!$countOnly) {
            if (in_array($this->dmsService->convertSnakeCaseString($role->getRoleKey()), [Constants::OBJECT_OWNER_ROLE, Constants::TENANT_ROLE])) {
                $param += ['editableUsers' => []];
            } else if ($this->dmsService->convertSnakeCaseString($role->getRoleKey()) === Constants::JANITOR_ROLE) {
                $param += ['editableUsers' => [$user]];
            } else {
                $param += ['editableUsers' => $users];
            }
        }

        $qb = $this->createQueryBuilder('f');
        if (is_null($parent)) {
            $where = "o.active = :isActive AND o.status = :status AND pu.isActive = :isActive AND pu.role = :role AND pu.user IN (:users)
                AND pr.identifier IN (:properties) AND pr.deleted = :deleted AND f.isPrivate = :isPrivate";
            $qb
                ->select($toSelect)
                ->leftJoin(Property::class, 'pr', 'WITH', 'pr.folder = f.identifier')
                ->join(Apartment::class, 'a', 'WITH', 'a.property = pr.identifier')
                ->join(ObjectContracts::class, 'o', 'WITH', 'o.object = a.identifier')
                ->join(PropertyUser::class, 'pu', 'WITH', 'pu.object = a.identifier
                    AND pu.contract = o.identifier AND pu.property = pr.identifier')
                ->where($where);
            $param += ['properties' => $properties, 'isPrivate' => false, 'isActive' => true, 'status' => 1,
                'role' => $role->getIdentifier(), 'users' => $users];
        } else {
            $parent = $this->findOneBy(['publicId' => $parent]);
            $orWhere = '';
            if (!is_null($parent->getParent())) {
                $orWhere = 'AND (c.active = :active AND o.active = :active)';
            }
            $qb
                ->select($toSelect)
                ->leftJoin(Folder::class, 'p', 'WITH', 'f.parent = p.identifier')
                ->leftJoin(Damage::class, 'da', 'WITH', 'da.folder = f.identifier OR da.folder = p.identifier')
                ->leftJoin(Apartment::class, 'a', 'WITH', '(a.folder = f.identifier OR a.folder = p.identifier) OR (da.apartment = a.identifier)')
                ->leftJoin(ObjectContracts::class, 'o', 'WITH', 'o.object = a.identifier')
                ->leftJoin(ObjectContracts::class, 'c', 'WITH', 'c.folder = f.identifier OR c.folder = p.identifier')
                ->leftJoin(PropertyUser::class, 'pu', 'WITH', 'pu.contract = c.identifier OR (pu.contract = o.identifier AND pu.object = a.identifier)')
                ->leftJoin(Property::class, 'pr', 'WITH', 'a.property = pr.identifier')
//                ->leftJoin(Damage::class, 'da', 'WITH', 'da.folder = f.identifier OR da.folder = p.identifier')
                ->where("f.parent = :parent AND pu.role = :role AND pu.user IN (:users) AND
                    f.displayName <> :message AND pu.isActive = :active AND pu.deleted = :deleted AND f.deleted = :deleted AND o.deleted = :deleted
                    AND (c.active = :active AND o.active = :active)")
//                ->orWhere("f.parent = :parent AND f.isPrivate = :isPrivate AND pu.role = :role AND (c.active IS NULL OR c.active = :active) AND pu.user IN (:users) AND f.displayName <> :message")
                ->orWhere("f.parent = :parent AND f.isPrivate = :isPrivate AND pu.role = :role AND pu.user IN (:users) AND f.displayName <> :message $orWhere")
                ->orWhere('f.parent = :parent AND f.isPrivate = :isPrivate AND (c.active = :active AND o.active = :active) AND da.folder IS NOT NULL AND pu.user IN (:users) AND f.displayName <> :message');
            $param += ['parent' => $parent->getIdentifier(), 'active' => true, 'role' => $role->getIdentifier(),
                'users' => $users, 'message' => 'messages', 'isPrivate' => false];
        }

        if (array_key_exists('search', $options) && !empty($options['search'])) {
            $qb->andWhere('f.displayName LIKE :displayName');
            $param['displayName'] = '%' . $options['search'] . '%';
            $parent = (isset($options['folder'])) ? $this->findOneBy(['publicId' => $options['folder']]) : null;
            if (null !== $parent) {
                $qb->andWhere('f.parent = :parent');
                $param['parent'] = $parent->getIdentifier();
            } else {
                $qb->andWhere('f.parent IS NULL');
            }
        }
        $qb->setParameters($param);
        if ($countOnly) {
            return $qb->getQuery()->getResult();
        }
        $qb->groupBy('pr.identifier, f.identifier')
            ->orderBy('f.createdAt', 'DESC');

        return $this->getCountAndType($qb->getQuery()->getResult(), $properties, $users, $user, $isRestricted, $role, $options);
    }

    /**
     * @param int $user
     * @param bool $isRestricted
     * @param Role $role
     * @param string|null $parent
     * @param array $options
     * @return array
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPropertyLevelUserFolders(int $user, bool $isRestricted, Role $role, ?string $parent, array $options = []): array
    {
        if (!is_null($parent)) {
            $parent = $this->registry->getRepository(Folder::class)->findOneBy(['publicId' => $parent]);
            $folders = $this->getPropertyAndObjectFolders($parent, $isRestricted, $role, false, $options, $user);
        } else {
            $folders = $this->getPropertyRoleBasedFolders($user, $isRestricted, $role, $parent, $options);
        }
        return $folders;
    }

    /**
     * @param int $user
     * @param bool $isRestricted
     * @param Role $role
     * @param string|null $parent
     * @param array $options
     * @param bool $countOnly
     * @return array
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getPropertyRoleBasedFolders(int $user, bool $isRestricted, Role $role, ?string $parent = null, array $options = [], bool $countOnly = false): array
    {
        $andWhere = $toCheckAdmin = '';
        $param = ['deleted' => false];
        if ($countOnly) {
            $toSelect = ['count(distinct f.identifier) as count'];
        } else {
            $toSelect = ['f.identifier, f.publicId, f.name, pr.active as isPropertyActive, f.path, f.displayName,f.isPrivate, f.isSystemGenerated, pr.publicId AS property, pr.identifier AS propertyId,' . $this->canEdit($isRestricted)];
            $param += ['editableUsers' => [$user]];
        }
        $qb = $this->createQueryBuilder('f')
            ->select($toSelect);
        $roleKey = $this->dmsService->convertSnakeCaseString($role->getRoleKey());
        if (is_null($parent)) {
            if ($roleKey === Constants::OWNER_ROLE) {
                $toCheck = 'pr.user';
            } elseif ($roleKey === Constants::PROPERTY_ADMIN_ROLE) {
                $toCheck = 'pr.administrator';
                $toCheckAdmin = 'pr.user';
            } else {
                $toCheck = 'pr.janitor';
            }
            if (empty($options)) {
                $andWhere = ' AND f.parent IS NULL';
            }
            $qb
                ->leftJoin(Folder::class, 'p', 'WITH', 'f.parent = p.identifier')
                ->leftJoin(Property::class, 'pr', 'WITH', 'pr.folder = f.identifier OR pr.folder = p.identifier')
                ->where($toCheck . ' IN (:users) AND pr.deleted = :deleted AND f.displayName != :message AND f.deleted = :deleted' . $andWhere);
            if (!empty(trim($toCheckAdmin))) {
                $qb->orWhere($toCheckAdmin . ' IN (:users) AND pr.deleted = :deleted AND f.displayName != :message AND f.deleted = :deleted' . $andWhere);
            }
            $param += ['users' => $user, 'message' => 'messages'];
        } else {
            $parent = $this->findOneBy(['publicId' => $parent]);
            if (!$countOnly) {
                $qb->addSelect('a.publicId AS apartment, a.active as isApartmentActive, oc.publicId AS contract, p.publicId AS parent, p.isPrivate as parentFolderAccessibility');
            }
            $qb
                ->leftJoin(Folder::class, 'p', 'WITH', 'p.identifier = f.parent')
                ->leftJoin(Property::class, 'pr', 'WITH', 'pr.folder = p.identifier OR pr.folder = f.identifier')
                ->leftJoin(Apartment::class, 'a', 'WITH', 'a.folder = f.identifier OR a.folder = p.identifier')
                ->leftJoin(ObjectContracts::class, 'oc', 'WITH', 'oc.folder = f.identifier')
                ->where('f.deleted = :deleted AND f.parent = :parent AND f.displayName != :message');
            $param += ['parent' => $parent->getIdentifier(), 'message' => 'messages'];
            if ($roleKey === Constants::JANITOR_ROLE) {
                $qb->andWhere('f.isPrivate = :isPrivate');
                $param += ['isPrivate' => false];
            }
        }

        if (array_key_exists('search', $options) && !empty($options['search'])) {
            $qb->andWhere('f.displayName LIKE :displayName');
            $param['displayName'] = '%' . $options['search'] . '%';
            $parent = (isset($options['folder'])) ? $this->findOneBy(['publicId' => $options['folder']]) : null;
            if (!is_null($parent)) {
                $qb->andWhere('f.parent = :parent');
                $param['parent'] = $parent->getIdentifier();
            } else {
                $qb->andWhere('f.parent IS NULL');
            }
        }
        $qb->setParameters($param);
        if ($countOnly) {
            return $qb->getQuery()->getResult();
        }
        $qb
            ->groupBy('pr.identifier, f.identifier')
            ->orderBy('f.createdAt', 'DESC');

        return $this->getCountAndType($qb->getQuery()->getResult(), [], [], $user, $isRestricted, $role, $options);
    }

    /**
     * @param Folder $parent
     * @param bool $isRestricted
     * @param Role $role
     * @param bool $countOnly
     * @param array $options
     * @param int|null $user
     * @return array
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getPropertyAndObjectFolders(Folder $parent, bool $isRestricted, Role $role, bool $countOnly = false, array $options = [], ?int $user = null): array
    {
        $param = [];
        if ($countOnly) {
            $toSelect = ['count(distinct f.identifier) as count'];
        } else {
            $toSelect = ['DISTINCT f.identifier, f.publicId, f.name, f.path, f.displayName, f.isPrivate, f.isSystemGenerated, 
                     pr.publicId AS property, a.publicId AS apartment, a.active as isApartmentActive, p.publicId AS parent,
                     p.isPrivate as parentFolderAccessibility, pu.isActive, pr.identifier AS propertyId, COALESCE(prp.active, pr.active, true) AS isPropertyActive ' . ((null !== $user) ? ',' . $this->canEdit($isRestricted) : '')];
            if (null !== $user) {
                $param += ['editableUsers' => [$user]];
            }
        }
        $param += ['deleted' => false, 'parent' => $parent->getIdentifier()];
        $qb = $this->createQueryBuilder('f')
            ->select($toSelect)
            ->leftJoin(Folder::class, 'p', 'WITH', 'p.identifier = f.parent')
            ->leftJoin(Property::class, 'pr', 'WITH', 'pr.folder = p.identifier OR pr.folder = f.identifier')
            ->leftJoin(Apartment::class, 'a', 'WITH', 'a.folder = f.identifier OR a.folder = p.identifier')
            ->leftJoin(ObjectContracts::class, 'oc', 'WITH', 'a.identifier = oc.object')
            ->leftJoin(PropertyUser::class, 'pu', 'WITH', 'pu.property = pr.identifier OR pu.object = a.identifier')
            ->leftJoin(Property::class, 'prp', 'WITH', 'prp.identifier = a.property')
            ->leftJoin(ObjectContracts::class, 'oct', 'WITH', 'oct.folder = f.identifier OR oct.folder = p.identifier')
            ->leftJoin(Damage::class, 'd', 'WITH', 'd.folder = f.identifier OR d.folder = p.identifier AND d.apartment = a.identifier')
            ->where('f.deleted = :deleted AND p.identifier = :parent AND f.displayName <> :message ');
        $param += ['message' => 'messages'];
        if ($this->dmsService->convertSnakeCaseString($role->getRoleKey()) === Constants::JANITOR_ROLE) {
            $qb->andWhere('f.isPrivate = :isPrivate OR f.displayName = :tickets')
                ->andWhere('oct.identifier IS NULL');
            $param += ['isPrivate' => false, 'tickets' => 'Tickets'];
        } else {
//            $qb->andWhere('(f.isPrivate = :isPrivate AND f.createdBy = :currentUser) OR f.isPrivate = false');
//            $param += ['isPrivate' => true, 'currentUser' => $user];
        }

        if (array_key_exists('search', $options) && !empty($options['search'])) {
            $qb->andWhere('f.displayName LIKE :displayName AND f.parent = :parent');
            $param += ['displayName' => '%' . $options['search'] . '%', 'parent' => $parent->getIdentifier()];
        }
        $qb->setParameters($param);

        if ($countOnly) {
            return $qb->getQuery()->getResult();
        }
        // $qb->addGroupBy('f.identifier');
        $qb->addGroupBy('f.identifier, f.publicId, f.name, f.path, f.displayName, f.isPrivate, f.isSystemGenerated, 
                 pr.publicId, a.publicId, a.active, p.publicId, p.isPrivate, pu.isActive, pr.identifier');
        // dd($qb->getQuery()->getResult());
        return $this->getCountAndType($qb->getQuery()->getResult(), [], [], null, $isRestricted, $role);
    }

    /**
     *
     * @param Folder $folder
     * @return void
     */
    public function deleteChildFolders(Folder $folder): void
    {
        $ids = $this->createQueryBuilder('f')
            ->join('App\Entity\Folder', 'p', 'WITH', 'p.identifier = f.parent')
            ->where('f.parent = :parent')
            ->select('f.identifier')
            ->setParameters(array('parent' => $folder))
            ->getQuery()->getResult();

        $qb = $this->createQueryBuilder('d');
        $query = $qb->update('App\Entity\Folder', 'd')
            ->set('d.deleted', ':deleted')
            ->where('d.identifier in (:ids)')
            ->setParameters(array('deleted' => true, 'ids' => $ids))
            ->getQuery();
        $query->execute();
    }

    /**
     *
     * @param UserIdentity|null $company
     * @return Folder|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCompanyLogoFolder(?UserIdentity $company = null): ?Folder
    {
        return $this->createQueryBuilder('f')
            ->join(Document::class, 'd', 'WITH', 'f.identifier = d.folder')
            ->where('f.createdBy = :company AND d.user = :company AND d.property IS NULL AND d.apartment IS NULL AND d.type = :type AND d.isActive = :isActive')
            ->orderBy('f.identifier', 'DESC')
            ->setParameters(['company' => $company, 'type' => 'coverImage', 'isActive' => true])
            ->getQuery()->getOneOrNullResult();
    }
}
