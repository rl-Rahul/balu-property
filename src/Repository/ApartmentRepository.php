<?php

/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Repository;

use App\Entity\Apartment;
use App\Entity\Damage;
use App\Entity\DamageStatus;
use App\Entity\Property;
use App\Entity\Floor;
use App\Entity\ObjectTypes;
use App\Entity\UserIdentity;
use App\Service\DMSService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Traits\RepositoryTrait;
use App\Entity\Folder;
use App\Entity\ObjectContractDetail;
use App\Entity\ContractTypes;
use App\Entity\ObjectContracts;
use App\Utils\Constants;
use App\Entity\PropertyUser;
use App\Entity\Role;

/**
 * ApartmentRepository
 * Repository used for user Apartment related queries
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 *
 * @method Apartment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Apartment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Apartment[]    findAll()
 * @method Apartment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApartmentRepository extends ServiceEntityRepository
{
    use RepositoryTrait;

    /**
     * @var DMSService $dmsService
     */
    private DMSService $dmsService;

    public function __construct(ManagerRegistry $registry, DMSService $dmsService)
    {
        $this->dmsService = $dmsService;
        parent::__construct($registry, Apartment::class);
    }

    /**
     * get all apartments with pagination
     *
     * @param Property $property
     * @param int|null $objectFilter
     * @param string|null $sortBy
     * @param int|null $startPage
     * @param array|null $filter
     * @param UserIdentity $user
     * @param string|null $sortOrder
     * @param int|null $count
     * @param Role|null $role
     * @param string $locale
     * @return array
     */
    public function getAllApartments(Property $property, ?int $objectFilter, ?string $sortBy, ?int $startPage, ?array $filter, UserIdentity $user, ?string $sortOrder = 'ASC', ?int $count = 0, ?Role $role = null, string $locale = ''): array
    {
        $locale = !empty($locale) && $locale == 'de' ? ucfirst($locale) : '';
        $where = 'a.property = :property AND a.deleted = :deleted';
        $params = ['property' => $property, 'deleted' => false];
        $qb = $this->createQueryBuilder('a')
            ->select('a.identifier, a.publicId, a.name, a.sortOrder, a.roomCount, a.ceilingHeight, a.volume, a.maxFloorLoading,
             a.officialNumber, a.area, a.active as isObjectActive, ot.name' . "$locale" . ' as objectType, f.floorNumber, a.isSystemGenerated, p.address')
            ->join('a.property', 'p')
            ->leftJoin(ObjectTypes::class, 'ot', 'WITH', 'a.objectType = ot.identifier')
            ->leftJoin(Floor::class, 'f', 'WITH', 'a.floor = f.identifier')
            ->leftJoin(PropertyUser::class, 'u', 'WITH', 'a.identifier = u.object')
            ->leftJoin(ObjectContracts::class, 'oc', 'WITH', 'oc.object = a.identifier');
        if (is_array($filter) && !empty($filter['text'])) {
            $where .= ' AND (a.name LIKE :searchTerms OR ot.name LIKE :searchTerms)';
            $params += ['searchTerms' => '%' . $filter['text'] . '%'];
        }
        if ($role instanceof Role) {
            $roleKey = $this->dmsService->convertSnakeCaseString($role->getRoleKey());
            if (!in_array($roleKey, [Constants::OWNER_ROLE, Constants::PROPERTY_ADMIN_ROLE, Constants::JANITOR_ROLE])) {
                $where .= ' AND (u.role = :role AND u.deleted = :deleted AND u.isActive = :active AND u.user = :user AND oc.active = :active AND a.isSystemGenerated = :isSystemGenerated)';
                $params += ['user' => $user, 'role' => $role, 'isSystemGenerated' => false];
            } elseif (!is_null($user)) {
                $params += ['user' => $user];
                if ($roleKey === Constants::OWNER_ROLE) {
                    $qb->join('p.user', 'user');
                    $where .= ' AND (p.user = :user OR user.administrator = :user)';
                } elseif ($roleKey === Constants::PROPERTY_ADMIN_ROLE) {
                    $where .= ' AND (p.administrator = :user OR p.user = :user)';
                } elseif ($roleKey === Constants::JANITOR_ROLE) {
                    $qb->join('p.janitor', 'user');
                    $where .= ' AND p.janitor = :user OR user.administrator = :user';
                } else {
                    // TODO
                }
            }
        } else {
            $where .= ' AND (u.deleted = :deleted AND u.isActive = :active AND u.user = :user AND oc.active = :active AND a.isSystemGenerated = :isSystemGenerated)';
            $params += ['user' => $user, 'isSystemGenerated' => false, 'active' => true, 'deleted' => false];
        }

        $qb->where($where)
            ->setParameters($params);
        if (isset($objectFilter) && 0 == $objectFilter) {
            $qb->andWhere('a.active = :active')
                ->setParameter('active', true);
        }
        if (null === $sortBy) {
            $sortBy = 'a.sortOrder';
        }
        if (!($sortOrder == 'ASC' || $sortOrder == 'DESC')) {
            $sortOrder = 'ASC';
        }
        $qb->orderBy($sortBy, $sortOrder);

        return $this->handlePagination($qb, $startPage, $count);
    }

    /**
     * Get Active damage count of given apartment
     * @param int $apartment
     * @param array $statusArray
     *
     * @return int
     * @throws
     */

    public function getActiveDamageCount(int $apartment, array $statusArray): int
    {
        $query = $this->createQueryBuilder('a')
            ->select('COUNT(d.identifier)')
            ->join(Damage::class, 'd', 'WITH', 'd.apartment = a.identifier')
            ->innerJoin(DamageStatus::class, 's', 'WITH', 's.identifier = d.status')
            ->where('a.identifier = :apartment')
            ->andWhere('s.key IN (:statusArray)')
            ->andWhere('a.active = :active')
            ->setParameters(array('apartment' => $apartment, 'active' => 1, 'statusArray' => $statusArray));

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * Get all apartment count mapped to o project and user
     *
     * @param int $property
     * @return int|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */

    public function getApartmentCount(int $property): ?int
    {
        $qb = $this->createQueryBuilder('a');
        $query = $qb->select('COUNT(a.identifier)')
            ->where('a.property = :property')
            ->andWhere('a.deleted = :deleted')
            ->setParameters(array('property' => $property, 'deleted' => false));

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * Get all apartment count mapped to o project and user
     *
     * @param int $property
     * @return int
     * @throws
     */
    public function getActiveApartmentCount(int $property): int
    {
        $qb = $this->createQueryBuilder('a');
        $query = $qb->select('COUNT(a.identifier)')
            ->where('a.property = :property')
            ->andWhere('a.active = :active')
            ->andWhere('a.deleted = :deleted')
            ->andWhere('a.isSystemGenerated = :isSystemGenerated')
            ->setParameters(array('property' => $property, 'active' => true, 'deleted' => false,
                'isSystemGenerated' => false));

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * Get all apartments based on the user and property
     *
     * @param Property $property
     * @param UserIdentity $user
     * @param string|null $locale
     *
     * @return array
     */
    public function getApartmentsOfUser(Property $property, UserIdentity $user, string $locale = null): array
    {
        $objType = !is_null($locale) ? ', o.name' . $locale : ', o.name';
        $objType .= ' AS objectType';
        $query = $this->createQueryBuilder('a')
            ->select('a.identifier, a.floor, a.floorName, u.firstName, t.contractStartDate, t.contractEndDate, t.identifier AS tenantId, t.active AS activeTenant' . $objType)
            ->leftJoin(Property::class, 'p', 'WITH', 'a.property = p.identifier')
            ->leftJoin(PropertyUser::class, 'pu', 'WITH', 'pu.object = a.identifier')
            ->join(UserIdentity::class, 'u', 'WITH', 'pu.user = u.identifier')
            ->join(ObjectTypes::class, 'o', 'WITH', 'o.identifier = a.objectType')
            ->where('t.user = :user')
            ->andWhere('t.active = :active OR t.contractStartDate >= :currentDate')
            ->andWhere('t.deleted = :deleted')
            ->andWhere('p.identifier = :property')
            ->andWhere('a.active = :active')
            ->setParameters(['user' => $user, 'property' => $property, 'active' => true, 'currentDate' => new \DateTime('today'), 'deleted' => false]);

        return $query->getQuery()->getResult();
    }

    /**
     * get property folders of a user
     *
     * @param array $params
     * @return array
     */
    public function getFolders(array $params): array
    {
        $params['deleted'] = false;
        $qb = $this->createQueryBuilder('p');
        $query = $qb
            ->select('f.identifier', 'f.name', 'f.publicId AS folderId', 'p.publicId', 'f.displayName', 'f.displayNameOffset')
            ->leftJoin(Folder::class, 'f', 'WITH', 'p.folder = f.identifier')
            ->where('p.createdBy = :user')
            ->andWhere('p.deleted = :deleted')
            ->orderBy('p.createdAt', 'DESC')
            ->setParameters($params);
        return $query->getQuery()->getResult();
    }

    /**
     * get filtered properties with pagination
     * @param Property $property
     * @param int|null $count
     * @param int|null $startPage
     * @param array $params
     * @return array
     */
    public function filterProperties(Property $property, ?int $count, ?int $startPage, array $params = []): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a.identifier, a.publicId, a.name, o.active as hasActiveContract, a.sortOrder, a.roomCount, a.ceilingHeight, a.officialNumber, a.area, ot.name as objectType,
               a.volume, a.maxFloorLoading, a.active as isObjectActive, f.floorNumber, a.isSystemGenerated, IDENTITY(oc.object)')
            ->where('a.property = :property')
            ->leftJoin(ObjectTypes::class, 'ot', 'WITH', 'a.objectType = ot.identifier')
            ->leftJoin(Floor::class, 'f', 'WITH', 'a.floor = f.identifier')
            ->andWhere('a.active = :active')
            ->andWhere('a.deleted = :deleted')
            ->setParameters(['property' => $property, 'active' => 1, 'deleted' => 0]);

        if (!empty($params['objectType'])) {
            $qb->andWhere('a.objectType IN (:object)')
                ->setParameter('object', $params['objectType']);
        }

        if (isset($params['activeContract'])) {
            $qb->leftJoin(ObjectContracts::class, 'o', 'WITH', 'o.object = a.identifier');
            if (false === $params['activeContract']) {
                $qb->andWhere('(o.active = 0 AND oc.active = 0) OR o.identifier is null');
            }
            if (true === $params['activeContract']) {
                $qb->andWhere('o.active = 1 AND oc.active = 1');
            }
        }

        $qb->addSelect('ct.nameEn as activeContractType');
        $qb->leftJoin(ObjectContractDetail::class, 'oc', 'WITH', 'oc.object = a.identifier')
            ->leftJoin(ContractTypes::class, 'ct', 'WITH', 'oc.contractType = ct.identifier');
        if (!empty($params['contractType'])) {
            if ($params['contractType'] == Constants::CONTRACT_TYPE_RENTAL) {
                $qb->andWhere('ct.type = :contractType')
                    ->setParameter('contractType', Constants::OBJECT_CONTRACT_TYPE_RENTAL);
            }
            if ($params['contractType'] == Constants::CONTRACT_TYPE_OWNERSHIP) {
                $qb->andWhere('ct.type = :contractType')
                    ->setParameter('contractType', Constants::OBJECT_CONTRACT_TYPE_OWNER);
            }
        }
        $qb->orderBy('a.sortOrder', 'ASC')
            ->groupBy('oc.object');

        return $this->handlePagination($qb, $startPage, $count);
    }

    /**
     *
     * @param Property $property
     * @return int|null
     * @throws
     */
    public function countObjects(Property $property): ?int
    {
        $params = ['deleted' => false, 'property' => $property];
        $qb = $this->createQueryBuilder('a');
        $query = $qb->select('COUNT(DISTINCT a.identifier) AS objectCount')
            ->where('a.deleted = :deleted')
            ->andWhere('a.property = :property');
//        if ($isDashboard) {
//            $qb->andWhere('a.active = :active');
//            $params += ['active' => true];
//        }
        $qb->setParameters($params);

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     *
     * @param Property $property
     * @param bool $status
     * @return void
     */
    public function setObjectStatus(Property $property, bool $status)
    {
        $this->createQueryBuilder('a')
            ->update()
            ->set('a.active', ':status')
            ->where('a.property = :property AND a.deleted = :deleted AND a.isSystemGenerated = :isSystemGenerated')
            ->setParameters(array('deleted' => false, 'property' => $property, 'status' => $status, 'isSystemGenerated' => false))
            ->getQuery()
            ->execute();

        $this->createQueryBuilder('a')
            ->update()
            ->set('a.active', ':status')
            ->where('a.property = :property AND a.deleted = :deleted AND a.isSystemGenerated = :isSystemGenerated')
            ->setParameters(array('deleted' => false, 'property' => $property, 'status' => true, 'isSystemGenerated' => true))
            ->getQuery()
            ->execute();
    }

    /**
     * Get all apartment count mapped to o project and user
     *
     * @param int $property
     * @return int
     * @throws
     */
    public function getTotalApartmentCount(int $property): int
    {
        $qb = $this->createQueryBuilder('a');
        $query = $qb->select('COUNT(a.identifier)')
            ->where('a.property = :property')
            ->andWhere('a.deleted = :deleted')
            ->setParameters(array('property' => $property, 'deleted' => false));

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $owner
     * @param array $users
     * @return array
     */
    public function getOwnerApartmentIdentifiers(int $owner, array $users): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.identifier')
            ->join('a.property', 'p')
            ->where('p.user = :owner AND a.createdBy IN (:users) AND a.property IS NOT NULL
             AND p.deleted = :deleted AND a.deleted = :deleted')
            ->distinct()
            ->setParameters(['deleted' => false, 'owner' => $owner, 'users' => $users]);
        return array_column($qb->getQuery()->getResult(), 'identifier');
    }

    /**
     * @param int $admin
     * @param array $users
     * @return array
     */
    public function getAdminApartmentIdentifiers(int $admin, array $users): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.identifier')
            ->join(Property::class, 'p', 'a.property = p.identifier')
            ->where('p.administrator = :admin AND a.createdBy IN (:users) AND a.property IS NOT NULL
             AND p.deleted = :deleted AND a.deleted = :deleted AND a.active = :active AND p.active = :active')
            ->distinct()
            ->setParameters(['deleted' => false, 'admin' => $admin, 'users' => $users, 'active' => true]);

        return array_column($qb->getQuery()->getResult(), 'identifier');
    }
}