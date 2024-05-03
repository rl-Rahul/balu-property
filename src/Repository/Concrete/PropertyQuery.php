<?php

namespace App\Repository\Concrete;

use App\Entity\Apartment;
use App\Entity\ObjectContracts;
use App\Entity\Property;
use App\Entity\PropertyUser;
use App\Entity\Role;
use App\Entity\UserIdentity;
use App\Repository\Interfaces\PropertyQueryInterface;
use App\Service\DMSService;
use App\Utils\Constants;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Class PropertyQuery
 * @package App\Repository\Concrete
 */
class PropertyQuery implements PropertyQueryInterface
{
    /**
     * @var DMSService $dmsService
     */
    private DMSService $dmsService;

    /**
     * @var EntityManagerInterface $entityManager
     */
    private EntityManagerInterface $entityManager;

    /**
     * PropertyQuery constructor.
     * @param EntityManagerInterface $entityManager
     * @param DMSService $dmsService
     */
    public function __construct(EntityManagerInterface $entityManager, DMSService $dmsService)
    {
        $this->entityManager = $entityManager;
        $this->dmsService = $dmsService;
    }

    /**
     * @param UserIdentity|null $user
     * @param array $params
     * @param Role|null $role
     * @return array
     */
    public function getProperties(UserIdentity $user = null, array $params = [], ?Role $role = null): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(Property::class, 'p')
            ->where('p.deleted = :deleted')
            ->setParameter('deleted', false)
            ->orderBy('p.identifier', 'DESC');
        if (isset($params['showdisabled']) && 0 == $params['showdisabled']) {
            $qb->andWhere('p.active = :active')
                ->setParameter('active', true);
        }
        if (isset($params['searchKey']) && !empty(trim($params['searchKey']))) {
            $qb->join('p.user', 'ui');
            $qb->andWhere("p.address LIKE :search OR ui.lastName LIKE :search OR CONCAT(ui.firstName, ' ', ui.lastName) like :search OR ui.companyName like :search")
                ->setParameter('search', '%' . $params['searchKey'] . '%');
        }
        if (!empty($params['limit']) || !empty($params['offset'])) {
            $qb->setMaxResults($params['limit'])
                ->setFirstResult($params['offset']);
        }
        $this->applyCurrentRole($qb, $user, $role);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param UserIdentity|null $user
     * @param Role|null $role
     * @param bool $isDashboard
     * @return int|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countProperties(UserIdentity $user = null, ?Role $role = null, bool $isDashboard = false): ?int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $params = ['deleted' => false];
        $query = $qb->select('COUNT(DISTINCT p.identifier) AS propertyCount')
            ->from(Property::class, 'p')
            ->where('p.deleted = :deleted');
        if ($isDashboard) {
            $qb->andWhere('p.active = :active');
            $params += ['active' => true];
        }
        $qb->setParameters($params);

        $this->applyCurrentRole($qb, $user, $role);

        return $query->getQuery()->getSingleScalarResult();
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
        $params = ['deleted' => false];
        $qb = $this->entityManager->createQueryBuilder();
        $query = $qb->select('COUNT(DISTINCT aa.identifier)')
            ->from(Property::class, 'p')
            ->join(Apartment::class, 'aa', 'WITH', 'aa.property = p.identifier')
            ->where('p.deleted = :deleted and aa.deleted = :deleted');
        if ($isDashboard) {
            $qb->andWhere('p.active = :active AND aa.isSystemGenerated = :isSystemGenerated');
            $params += ['active' => true, 'isSystemGenerated' => false];
        }
        $qb->setParameters($params);

        $this->applyCurrentRole($qb, $user, $role);

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $user
     * @param string $role
     * @return array
     */
    public function findRelatedObjectLevelUsers(int $user, string $role): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('users.identifier')
            ->from(Property::class, 'p')
            ->join(Apartment::class, 'a', 'WITH', 'a.property = p.identifier')
            ->join(PropertyUser::class, 'pu', 'WITH', 'pu.object = a.identifier');
        if (in_array($role, ['owner', 'property_admin', 'janitor'])) {
            $qb->join('pu.user', 'users')
                ->where('pu.deleted = false AND pu.isActive = true');
            $this->switchRole($qb, $role);
            $qb->setParameters(['user' => $user]);
        } else {
            $qb->join('pu.role', 'role')
                ->leftJoin(PropertyUser::class, 'puu', 'WITH', 'puu.object = a.identifier')
                ->leftJoin('puu.user', 'users')
                ->where('pu.user = :user AND role.roleKey = :roleKey AND pu.deleted = false AND pu.isActive = true'
                    . ' AND puu.deleted = false AND puu.isActive = true')
                ->setParameters(['user' => $user, 'roleKey' => $role]);
        }

        return array_unique(array_column($qb->distinct()->getQuery()->getResult(), 'identifier'));
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
        $qb = $this->entityManager->createQueryBuilder();
        $query = $qb->select('COUNT(DISTINCT uii.identifier)')
            ->from(Property::class, 'p')
            ->join(Apartment::class, 'apr', 'WITH', 'apr.property = p.identifier')
            ->join(ObjectContracts::class, 'o', 'WITH', 'o.object = apr.identifier')
            ->join(PropertyUser::class, 'pu', 'WITH', 'pu.contract = o.identifier')
            ->join(UserIdentity::class, 'uii', 'WITH', 'pu.user = uii.identifier')
            ->join('pu.contract', 'cn')
            ->where('p.deleted = :deleted and apr.deleted = :deleted AND (pu.role IN (:role)) AND pu.isActive= :active AND cn.active= :active ');
        if ($isDashboard) {
            $qb->andWhere('p.active = :active');
        }
        $roles = $this->entityManager->getRepository(Role::class)->findBy(['roleKey' => [Constants::TENANT_ROLE, 'object_owner']]);
        $qb->setParameters(['deleted' => false, 'active' => true, 'role' => $roles]);

        $this->applyCurrentRole($qb, $user, $role);

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $user
     * @param string $role
     * @return array
     */
    public function findRelatedPropertyLevelUsers(int $user, string $role): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('owner.identifier as ownerId, administrator.identifier as administratorId, janitor.identifier as janitorId')
            ->from(Property::class, 'p')
            ->leftJoin(UserIdentity::class, 'owner', 'WITH', 'p.user = owner.identifier')
            ->leftJoin(UserIdentity::class, 'administrator', 'WITH', 'p.administrator = administrator.identifier')
            ->leftJoin(UserIdentity::class, 'janitor', 'WITH', 'p.janitor = janitor.identifier');
        if (in_array($role, [Constants::OWNER_ROLE, $this->dmsService->convertCamelCaseString(Constants::PROPERTY_ADMIN_ROLE), Constants::JANITOR_ROLE])) {
            $this->switchRole($qb, $role);
            $qb->setParameters(['user' => $user]);
        } else {
            $qb->join(Apartment::class, 'a', 'WITH', 'a.property = p.identifier')
                ->join(PropertyUser::class, 'pu', 'WITH', 'pu.object = a.identifier')
                ->join('pu.role', 'role')
                ->where('pu.user = :user AND role.roleKey = :roleKey AND pu.deleted = false AND pu.isActive = true')
                ->setParameters(['user' => $user, 'roleKey' => $role]);
        }
        $result = $qb->distinct()->getQuery()->getResult();
        return array_unique(array_merge(array_column($result, 'ownerId'), array_column($result, 'administratorId'), array_column($result, 'janitorId')));
    }

    /**
     * Apply Current Role
     *
     * @param QueryBuilder $qb
     * @param UserIdentity |null $user
     * @param Role|null $role |null $role
     * @return void
     */
    private function applyCurrentRole(QueryBuilder $qb, UserIdentity $user = null, ?Role $role = null): void
    {
        if ($role instanceof Role) {
            $roleKey = $this->dmsService->convertSnakeCaseString($role->getRoleKey());
            if (!in_array($roleKey, [Constants::OWNER_ROLE, Constants::PROPERTY_ADMIN_ROLE, Constants::JANITOR_ROLE, Constants::ADMIN_ROLE])) {
                $qb->join(Apartment::class, 'a', 'WITH', 'a.property = p.identifier')
                    ->join(ObjectContracts::class, 'oc', 'WITH', 'a.identifier = oc.object')
                    ->join(PropertyUser::class, 'u', 'WITH', 'oc.identifier = u.contract')
                    ->join('u.user', 'user')
                    ->where('u.role = :role AND u.isActive = :active AND u.deleted = :deleted')
                    ->andWhere('u.user = :user OR user.administrator = :user')
                    ->setParameters(['role' => $role, 'user' => $user, 'deleted' => false, 'active' => true]);
            } elseif (!is_null($user) && $roleKey !== Constants::ADMIN_ROLE) {
                if ($roleKey === Constants::OWNER_ROLE) {
                    $qb->join('p.user', 'user');
                    $qb->andWhere('p.user = :user OR user.administrator = :user');
                } elseif ($roleKey === Constants::PROPERTY_ADMIN_ROLE) {
                    $qb->leftJoin('p.administrator', 'user');
//                    $qb->andWhere('p.administrator = :user OR user.administrator = :user OR p.user = :user');
                    $qb->andWhere('p.administrator = :user');
                } elseif ($roleKey === Constants::JANITOR_ROLE) {
                    $qb->join('p.janitor', 'user');
                    $qb->andWhere('p.janitor =:user OR user.administrator = :user');
                }
                $qb->setParameter('user', $user);
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param string $role
     * @return QueryBuilder
     */
    private function switchRole(QueryBuilder $qb, string $role): QueryBuilder
    {
        switch ($role) {
            case Constants::JANITOR_ROLE:
                $qb->where('p.janitor = :user');
                break;
            case Constants::OWNER_ROLE:
                $qb->where('p.user = :user');
                break;
            case $this->dmsService->convertCamelCaseString(Constants::PROPERTY_ADMIN_ROLE):
                $qb->where('p.administrator = :user');
                break;
            default:
                break;
        }

        return $qb;
    }
}