<?php

namespace App\Repository;

use App\Entity\Folder;
use App\Entity\Property;
use App\Entity\UserIdentity;
use App\Repository\Concrete\PropertyQuery;
use App\Utils\Constants;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Role;
use App\Entity\SubscriptionPlan;

/**
 * @method Property|null find($id, $lockMode = null, $lockVersion = null)
 * @method Property|null findOneBy(array $criteria, array $orderBy = null)
 * @method Property[]    findAll()
 * @method Property[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PropertyRepository extends ServiceEntityRepository
{
    /**
     * @var PropertyQuery $propertyQuery
     */
    private PropertyQuery $propertyQuery;

    /**
     * PropertyRepository constructor.
     * @param ManagerRegistry $registry
     * @param PropertyQuery $propertyQuery
     */
    public function __construct(ManagerRegistry $registry, PropertyQuery $propertyQuery)
    {
        parent::__construct($registry, Property::class);
        $this->propertyQuery = $propertyQuery;
    }

    /**
     * get all properties with pagination
     *
     * @param UserIdentity|null $user
     * @param array $params
     * @param Role|null $role |null $role
     * @return array
     */
    public function getProperties(UserIdentity $user = null, array $params = array(), ?Role $role = null): array
    {
        return $this->propertyQuery->getProperties($user, $params, $role);
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
            ->where('p.user = :user')
            ->andWhere('p.deleted = :deleted')
            ->orderBy('p.createdAt', 'DESC')
            ->setParameters($params);
        return $query->getQuery()->getResult();
    }

    /**
     * get count of properties
     *
     * @param UserIdentity|null $user
     * @param Role|null $role |null $role
     * @param bool $isDashboard
     * @return int|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countProperties(UserIdentity $user = null, ?Role $role = null, bool $isDashboard = false): ?int
    {
        return $this->propertyQuery->countProperties($user, $role, $isDashboard);
    }

    /**
     * @param array $param
     * @param string $userRole
     * @return array
     */
    public function findProperties(array $param, string $userRole = Constants::OWNER_ROLE): array
    {
        if ($userRole == Constants::OWNER_ROLE) {
            $andWhere = 'p.user = :user';
        } else {
            $andWhere = '(p.user = :user OR p.administrator = :user)';
        }
        $param['deleted'] = false;
        $qb = $this->createQueryBuilder('p')
            ->select('p.identifier')
            ->where('p.deleted = :deleted AND ' . $andWhere)
            ->setParameters($param);
        return array_column($qb->getQuery()->getResult(), 'identifier');
    }


    /**
     *
     * @param UserIdentity $admin
     * @param UserIdentity $user
     * @return array
     */
    public function getAdminDetails(UserIdentity $admin, UserIdentity $user): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p.publicId, p.address as propertyName');
        $query = $qb->where('p.deleted = :deleted')
            ->setParameter('deleted', false)
            ->andWhere('p.user = :user')
            ->andWhere('p.administrator = :administrator')
            ->setParameter('administrator', $admin)
            ->setParameter('user', $user);

        return $query->distinct()->getQuery()->getResult();
    }

    /**
     *
     * @param UserIdentity $company
     * @param UserIdentity $user
     * @return array
     */
    public function getJanitorAllocations(UserIdentity $company, UserIdentity $user): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p.publicId, p.address as propertyName');
        $query = $qb->where('p.deleted = :deleted')
            ->setParameter('deleted', false)
            ->andWhere('p.user = :user')
            ->andWhere('p.janitor = :janitor')
            ->setParameter('janitor', $company)
            ->setParameter('user', $user);

        return $query->distinct()->getQuery()->getResult();
    }

    /**
     * @param int $administrator
     * @return array
     */
    public function findOwners(int $administrator): array
    {
        $qb = $this->createQueryBuilder('ui')
            ->select('u.identifier')
            ->leftJoin(UserIdentity::class, 'u', 'WITH', 'ui.user = u.identifier')
            ->where('ui.administrator = :administrator AND ui.deleted = :deleted')
            ->setParameters(['deleted' => false, 'administrator' => $administrator]);

        return array_column($qb->distinct()->getQuery()->getResult(), 'identifier');
    }

    /**
     *
     * @param int $owner
     * @return array
     */
    public function findPropertyAdmins(int $owner): array
    {
        $qb = $this->createQueryBuilder('ui')
            ->select('u.identifier')
            ->join(UserIdentity::class, 'u', 'WITH', 'ui.administrator = u.identifier')
            ->where('ui.user = :owner AND ui.deleted = :deleted')
            ->setParameters(['deleted' => false, 'owner' => $owner]);

        return array_column($qb->distinct()->getQuery()->getResult(), 'identifier');
    }

    /**
     * @param int $user
     * @param string $role
     * @return array
     */
    public function findRelatedPropertyLevelUsers(int $user, string $role): array
    {
        return $this->propertyQuery->findRelatedPropertyLevelUsers($user, $role);
    }

    /**
     * @param int $user
     * @param string $role
     * @return array
     */
    public function findRelatedObjectLevelUsers(int $user, string $role): array
    {
        return $this->propertyQuery->findRelatedObjectLevelUsers($user, $role);
    }

    /**
     * get total number of objects
     *
     * @param UserIdentity|null $user
     * @param Role|null $role |null $role
     * @param bool $isDashboard
     * @return int|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countObjects(UserIdentity $user = null, ?Role $role = null, bool $isDashboard = false): ?int
    {
        return $this->propertyQuery->countObjects($user, $role, $isDashboard);
    }

    /**
     * get total number of tenants
     *
     * @param UserIdentity|null $user
     * @param Role|null $role |null $role
     * @param bool $isDashboard
     * @return int|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countTenants(UserIdentity $user = null, ?Role $role = null, bool $isDashboard = false): ?int
    {
        return $this->propertyQuery->countTenants($user, $role, $isDashboard);
    }

    /**
     * get expiring and expired properties of user with pagination
     *
     * @param UserIdentity $user
     * @param int $expirationLimit
     * @param array|null $params
     * @param bool|null $countOnly
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getExpiringProperties(UserIdentity $user, int $expirationLimit, ?array $params = null, ?bool $countOnly = false)
    {
        $curDate = new \DateTime('now');
        $qb = $this->createQueryBuilder('p');
        $query = $qb->where('p.user = :user')
            ->andWhere('p.deleted = :isDeleted')
            ->andWhere('p.recurring = :recurring')
            ->andWhere('TIMEDIFF(p.planEndDate, :curDate) < :expirationLimit')
            ->setParameters(array('user' => $user, 'isDeleted' => false, 'recurring' => 0, 'expirationLimit' => $expirationLimit, 'curDate' => $curDate))
            ->orderBy('p.createdAt', 'DESC');
        if (!empty($params['limit'])) {
            $qb->setMaxResults($params['limit']);
        }
        if (!empty($params['offset'])) {
            $qb->setFirstResult($params['offset']);
        }

        if ($countOnly) {
            $qb->select('count(distinct p.identifier) as count');
            return $query->getQuery()->getSingleScalarResult();
        }

        return $query->getQuery()->getResult();
    }

    /**
     * get All Expiring properties (5 days before expiry and 1 day before expiry)
     *
     * @param int $expirationLimit
     * @param int $expirationLimitFinal
     * @return array
     */
    public function getAllExpiringProperties(int $expirationLimit, int $expirationLimitFinal): array
    {
        $curDate = new \DateTime('now');
        $qb = $this->createQueryBuilder('p');
        $query = $qb->where('p.deleted = :deleted')
            ->andWhere('p.recurring = :recurring')
            ->andWhere('DATE_DIFF(p.planEndDate, :curDate) = :expirationLimit OR DATE_DIFF(p.planEndDate, :curDate) = :expirationLimitFinal')
            ->setParameters(array('deleted' => false, 'recurring' => 0, 'expirationLimit' => $expirationLimit, 'expirationLimitFinal' => $expirationLimitFinal, 'curDate' => $curDate));
        return $query->getQuery()->getResult();
    }

    /**
     *
     * @param UserIdentity $user
     * @param array|null $params
     * @param bool|null $countOnly
     * @return array
     * @throws
     */
    public function getActiveProperties(UserIdentity $user, ?array $params = [], ?bool $countOnly = false): array
    {
        $subscriptionPlan = $this->getEntityManager()->getRepository(SubscriptionPlan::class)->findOneBy(['initialPlan' => 1, 'active' => 1]);
        $curDate = new \DateTime('now');
        $qb = $this->createQueryBuilder('p');
        $query = $qb->where('p.user = :user')
//                    ->andWhere('p.recurring <> :recurring')
            ->andWhere('p.deleted = :isDeleted')
            ->andWhere('p.subscriptionPlan <> :initialPlan')
            ->andWhere('p.planEndDate > :curDate')
//                    ->setParameters(array('initialPlan' => $subscriptionPlan, 'user' => $user,'isDeleted' => false, 'recurring' => 1,'curDate' => $curDate))
            ->setParameters(array('initialPlan' => $subscriptionPlan, 'user' => $user, 'isDeleted' => false, 'curDate' => $curDate))
            ->orderBy('p.createdAt', 'DESC');
        if (!empty($params['limit'])) {
            $qb->setMaxResults($params['limit']);
        }
        if (!empty($params['offset'])) {
            $qb->setFirstResult($params['offset']);
        }

        if ($countOnly) {
            $qb->select('count(distinct p.identifier) as count');
            return $query->getQuery()->getSingleScalarResult();
        }
        return $query->getQuery()->getResult();

    }

    /**
     * get All Expiring properties (5 days before expiry and 1 day before expiry)
     *
     * @param UserIdentity $user
     * @param int $expirationLimit
     * @return array
     */
    public function getInitialPlanProperties(UserIdentity $user, int $expirationLimit): array
    {
        $subscriptionPlan = $this->getEntityManager()->getRepository(SubscriptionPlan::class)->findOneBy(['initialPlan' => 1, 'active' => 1]);
        $curDate = new \DateTime('now');
        $qb = $this->createQueryBuilder('p');
        $query = $qb->where('p.user = :user')
            ->andWhere('p.deleted = :isDeleted')
            ->andWhere('p.active = :active')
            ->andWhere('p.subscriptionPlan = :initialPlan')
            ->andWhere('DATE_DIFF(p.planEndDate, :curDate) > :expirationLimit OR p.planEndDate >= :curDate')
            ->setParameters(array('initialPlan' => $subscriptionPlan, 'user' => $user, 'isDeleted' => false, 'active' => 1, 'expirationLimit' => $expirationLimit, 'curDate' => $curDate))
            ->orderBy('p.createdAt', 'DESC');

        return $query->getQuery()->getResult();
    }

    /**
     * get All Expired Properties
     *
     * @return array
     */
    public function getAllExpiredProperties(): array
    {
        $curDate = new \DateTime('now');
        $qb = $this->createQueryBuilder('p');
        $query = $qb->where('p.deleted = :isDeleted')
            ->andWhere('p.active = :active')
            ->andWhere('p.recurring = :recurring')
            ->andWhere('DATE_DIFF(p.planEndDate, :curDate) < 0')
            ->setParameters(array('active' => true, 'isDeleted' => false, 'recurring' => false, 'curDate' => $curDate));

        return $query->getQuery()->getResult();
    }

    /**
     * get property details to fetch
     *
     * @return array
     */
    private function propertyDetailsToFetch(): array
    {
        return [
            'p.identifier', 'p.publicId', 'p.address', 'p.streetName', 'p.active', 'p.streetNumber', 'p.active',
            'p.postalCode', 'p.planEndDate', 'p.planStartDate', 's.identifier AS subscriptionPlan', 's.name',
            'CASE WHEN DATE_DIFF(p.planEndDate, :curDate) < :expirationLimit AND p.active = :active THEN true ELSE false END AS isExpiring',
            'CASE WHEN DATE_DIFF(p.planEndDate, :curDate) < :expiredDifference AND p.active = :deactivated THEN true ELSE false END AS isExpired',
            'CASE WHEN p.planEndDate > :curDate AND p.subscriptionPlan <> :initialPlan THEN true ELSE false END AS isActive',
            'CASE WHEN p.subscriptionPlan = :initialPlan AND (DATE_DIFF(p.planEndDate, :curDate) > :expirationLimit OR p.planEndDate >= :curDate) THEN true ELSE false END AS isFreePlan',
        ];
    }

    /**
     * get expiring and expired properties of user with pagination
     *
     * @param UserIdentity $user
     * @param int $expirationLimit
     * @param array|null $params
     * @param bool|null $countOnly
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPropertiesWithSubscriptions(UserIdentity $user, int $expirationLimit, ?array $params = null, ?bool $countOnly = false): array
    {
        $subscriptionPlan = $this->getEntityManager()->getRepository(SubscriptionPlan::class)->findOneBy(['initialPlan' => 1, 'active' => 1]);
        $curDate = new \DateTime('now');
        $qb = $this->createQueryBuilder('p');
        $query = $qb->where('p.user = :user');
        if ($countOnly) {
            $qb->select('count(distinct p.identifier) as count');
        } else {
            $qb->select($this->propertyDetailsToFetch())
                ->leftJoin(SubscriptionPlan::class, 's', 'WITH', 's.identifier = p.subscriptionPlan')
                ->andWhere('p.deleted = :isDeleted')
//                ->andWhere('p.recurring = :recurring')
                ->setParameters(array('user' => $user, 'isDeleted' => false, 'active' => true, 'expiredDifference' => 0,
                    'deactivated' => false, 'expirationLimit' => $expirationLimit, 'curDate' => $curDate, 'initialPlan' => $subscriptionPlan))
                ->orderBy('p.createdAt', 'DESC');
        }
        if (!empty($params['limit'])) {
            $qb->setMaxResults($params['limit']);
        }
        if (!empty($params['offset'])) {
            $qb->setFirstResult($params['offset']);
        }
        return $query->getQuery()->getResult();
    }

    /**
     * @param int $admin
     * @return array
     */
    public function findPropertyOwners(int $admin): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('IDENTITY (p.user) AS owner')
            ->where('p.administrator = :admin AND p.deleted = :deleted')
            ->distinct()
            ->setParameters(['deleted' => false, 'admin' => $admin]);

        return array_column($qb->distinct()->getQuery()->getResult(), 'owner');
    }

    /**
     * @param int $janitor
     * @return array
     */
    public function findPropertyOwnersAndAdminsForJanitor(int $janitor): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('IDENTITY (p.user) AS owner, IDENTITY (p.administrator) AS admin')
            ->where('p.janitor = :janitor AND p.deleted = :deleted')
            ->distinct()
            ->setParameters(['deleted' => false, 'janitor' => $janitor]);

        $result = $qb->distinct()->getQuery()->getArrayResult();
        $data['owner'] = array_values(array_unique(array_filter(array_column($result, 'owner'))));
        $data['admin'] = array_values(array_unique(array_filter(array_column($result, 'admin'))));
        $data['janitor'] = (array)$janitor;
        return $data;
    }

    /**
     * @param int $owner
     * @return array
     */
    public function findPropertyJanitorsOfOwners(int $owner): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('IDENTITY (p.janitor) AS janitor')
            ->where('p.user = :owner AND p.deleted = :deleted')
            ->distinct()
            ->setParameters(['deleted' => false, 'owner' => $owner]);

        return array_unique(array_filter(array_column($qb->distinct()->getQuery()->getResult(), 'janitor')));
    }

    /**
     * @param int $admin
     * @return array
     */
    public function findPropertyJanitorsForPropertyAdmins(int $admin): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('IDENTITY (p.janitor) AS janitor, p.identifier AS property')
            ->where('p.administrator = :administrator AND p.deleted = :deleted')
            ->distinct()
            ->setParameters(['deleted' => false, 'administrator' => $admin]);

        $data['janitor'] = array_unique(array_filter(array_column($qb->distinct()->getQuery()->getResult(), 'janitor')));
        $data['property'] = array_unique(array_filter(array_column($qb->distinct()->getQuery()->getResult(), 'property')));
        return $data;
    }
}
