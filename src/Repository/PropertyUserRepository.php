<?php

namespace App\Repository;

use App\Entity\DamageOffer;
use App\Entity\PropertyRoleInvitation;
use App\Entity\PropertyUser;
use App\Entity\UserDevice;
use App\Utils\Constants;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Apartment;
use App\Entity\ObjectContracts;
use App\Entity\Directory;
use App\Entity\UserIdentity;
use App\Entity\Role;
use App\Entity\Property;
use App\Entity\RentalTypes;
use App\Entity\User;
use App\Entity\ObjectContractDetail;
use App\Entity\ContractTypes;
use App\Entity\Damage;

/**
 * @method PropertyUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method PropertyUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method PropertyUser[]    findAll()
 * @method PropertyUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PropertyUserRepository extends ServiceEntityRepository
{
    /**
     * PropertyUserRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyUser::class);
    }

    /**
     * @param Apartment $apartment
     * @return int|mixed|string
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getActiveUserCount(Apartment $apartment)
    {
        $params['deleted'] = false;
        $params['object'] = $apartment;
//        $params['active'] = 1;
        $qb = $this->createQueryBuilder('p');
        $query = $qb->select('COUNT(DISTINCT p.identifier) AS propertyCount')
            ->leftJoin(Apartment::class, 'a', 'WITH', 'p.object = a.identifier')
            ->leftJoin(ObjectContracts::class, 'c', 'WITH', 'c.object = a.identifier')
//            ->where('c.active = :active')
            ->andWhere('p.object = :object')
            ->andWhere('p.deleted = :deleted')
            ->setParameters($params);

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     *
     * @param UserIdentity $oUser
     * @param UserIdentity $user
     * @param string $locale
     * @param string $currentRole
     * @param string|null $property
     * @return array
     * @throws EntityNotFoundException
     */
    public function getUserDetail(UserIdentity $oUser, UserIdentity $user, string $locale, string $currentRole, ?string $property = null): array
    {
        $params = ['deleted' => false, 'user' => $oUser->getIdentifier()];
        $qb = $this->createQueryBuilder('pu');
        $qb->select("p.publicId as propertyId, o.publicId as objectId, r.name as roleName, r.roleKey,
                     o.name as objectName, p.address as propertyName, c.startDate, c .endDate,
                     rt.name" . ucfirst($locale) . ", rt.name" . ucfirst($locale) . ", c.status, c.publicId as contractId,
                     ct.name" . ucfirst($locale) . " as contractTypeName");
        if (!is_null($property)) {
            $property = $this->_em->getRepository(Property::class)->findOneBy(['publicId' => $property]);
            if (!$property instanceof Property) {
                throw new EntityNotFoundException('invalidProperty');
            }
            $qb->addSelect('d.identifier AS directory')
                ->join(Directory::class, 'd', 'WITH', 'pu.property = d.property AND pu.user = d.user')
                ->join('d.user', 'ui')
                ->join('ui.user', 'u');
            $andWhere = 'p.identifier = :property';
            $params += ['property' => $property->getIdentifier()];
        } else {
            $andWhere = 'p.user = :createdBy';
            if ($currentRole === Constants::PROPERTY_ADMIN_ROLE) {
                $andWhere = 'p.administrator = :createdBy';
            }
            $params += ['createdBy' => $user->getIdentifier()];
        }
        $qb->leftJoin(Role::class, 'r', 'WITH', 'pu.role = r.identifier')
            ->leftJoin(Apartment::class, 'o', 'WITH', 'o.identifier = pu.object')
            ->leftJoin(Property::class, 'p', 'WITH', 'o.property = p.identifier')
            ->leftJoin(ObjectContracts::class, 'c', 'WITH', 'c.object = o.identifier')
            ->leftJoin(RentalTypes::class, 'rt', 'WITH', 'c.rentalType = rt.identifier')
            ->leftJoin(ObjectContractDetail::class, 'cd', 'WITH', 'cd.object = o.identifier')
            ->leftJoin(ContractTypes::class, 'ct', 'WITH', 'cd.contractType = ct.identifier')
            ->where('pu.deleted = :deleted')
            ->andWhere('pu.user = :user')
            ->andWhere($andWhere)
            ->setParameters($params);

        return $qb->distinct()->getQuery()->getResult();
    }

    /**
     *
     * @param ObjectContracts $objectContract
     * @param UserIdentity $user
     * @return array
     */
    public function getTenants(ObjectContracts $objectContract, UserIdentity $user): array
    {
        $qb = $this->createQueryBuilder('pu');
        $qb->select('u.identifier as userIdentifier', 'r.name as roleName', 'r.roleKey as role', 'u.publicId AS userPublicId',
            'u.companyName AS companyName', 'CONCAT(d.firstName, \' \', d.lastName) AS name', 'd.publicId AS directoryId',
            'd.phone AS phone', 'user.property AS email')
            ->addSelect('CASE WHEN user.firstLogin IS NOT NULL THEN true ELSE false END AS isRegisteredUser');
        $query = $qb->where('pu.deleted = :deleted')
            ->join(Directory::class, 'd', 'WITH', 'pu.user = d.user')
            ->join(UserIdentity::class, 'u', 'WITH', 'pu.user = u.identifier')
            ->join('u.user', 'user')
            ->leftJoin(Role::class, 'r', 'WITH', 'pu.role = r.identifier')
            ->leftJoin(ObjectContracts::class, 'c', 'WITH', 'pu.contract = c.identifier')
            ->andWhere('pu.contract = :contract AND pu.isActive = :isActive')
            ->andWhere('d.invitor = :user OR d.publicId IS NULL')
            ->groupBy('u.identifier')
            ->setParameters(['contract' => $objectContract, 'user' => $user->getIdentifier(), 'deleted' => false, 'isActive' => true]);

        return $query->distinct()->getQuery()->getResult();
    }

    /**
     * @param array $properties
     * @param UserIdentity $userIdentity
     * @return array
     */
    public function findAllocations(array $properties, UserIdentity $userIdentity): array
    {
        $qb = $this->createQueryBuilder('pu')
            ->select('pu.identifier')
            ->where('pu.property IN (:properties) AND pu.user = :user AND pu.deleted = :deleted')
            ->setParameters(['properties' => $properties, 'user' => $userIdentity, 'deleted' => false]);
        return array_column($qb->getQuery()->getResult(), 'identifier');
    }

    /**
     * @param int $param
     * @param string $type
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function activeTenantCount(int $param, string $type): int
    {
        $where = 'pu.deleted = :deleted AND pu.isActive = :active AND ';
        if ($type == 'property') {
            $where .= 'pu.property = :property';
            $params = ['property' => $param, 'active' => true, 'deleted' => false];
        } elseif ($type === 'contract') {
            $where .= 'pu.contract = :contract';
            $params = ['contract' => $param, 'active' => true, 'deleted' => false];
        } else {
            $where .= 'pu.object = :object';
            $params = ['object' => $param, 'active' => true, 'deleted' => false];
        }
        $qb = $this->createQueryBuilder('pu')
            ->select('COUNT(pu.identifier) AS tenantCount')
            ->where($where)
            ->setParameters($params);
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * check if given user is an active tenant
     *
     * @param int $apartment
     * @param int $tenantId
     *
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getActiveTenant(int $apartment, int $tenantId): array
    {
        $qb = $this->createQueryBuilder('t');
        $query = $qb->where('t.user = :tenantId')
            ->andWhere('t.object = :apartment')
            ->andWhere('t.isActive = :active')
            ->setParameters(['apartment' => $apartment, 'tenantId' => $tenantId, 'active' => true]);

        return $query->getQuery()->getOneOrNullResult();
    }


    /**
     * check if given user has requested role in the property
     *
     * @param int $apartment
     * @param int $userId
     * @param string $role
     *
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function checkIfUserHasActiveRole(int $apartment, int $userId, string $role): ?PropertyUser
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin(Role::class, 'r', 'WITH', 't.role = r.identifier');
        $query = $qb->where('t.user = :userId')
            ->andWhere('t.object = :apartment')
            ->andWhere('t.isActive = :active')
            ->andWhere('r.roleKey = :roleKey')
            ->setParameters(['apartment' => $apartment, 'userId' => $userId, 'roleKey' => $role, 'active' => true])
            ->setMaxResults(1);

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * returns active users of an apartment by role
     *
     * @param int $apartment
     * @param string|null $role
     *
     * @return array
     */
    public function getActiveUsersByRole(int $apartment, ?string $role = null): array
    {
        $users = [];
        $qb = $this->createQueryBuilder('t')
            ->leftJoin(Role::class, 'r', 'WITH', 't.role = r.identifier')
            ->andWhere('t.object = :apartment')
            ->andWhere('t.isActive = :active AND t.deleted = false');
        $params = ['apartment' => $apartment, 'active' => true];
        if (null !== $role) {
            $qb->andWhere('r.roleKey = :roleKey');
            $params['roleKey'] = $role;
        }
        $qb->setParameters($params);
        $result = $qb->getQuery()->getResult();
        foreach ($result as $user) {
            $users[] = $user->getUser();
        }

        return $users;
    }

    /**
     *
     * @param UserIdentity $company
     * @param UserIdentity $user
     * @return array
     */
    public function getCompanyDetail(UserIdentity $company, UserIdentity $user): array
    {
        $qb = $this->createQueryBuilder('pu');
        $qb->select('p.publicId, r.name as roleName, r.roleKey, o.name as objectName, p.address as propertyName, c.startDate, c .endDate, rt.nameEn, rt.nameDe, c.active');
        $query = $qb->where('pu.deleted = :deleted')
            ->leftJoin(Role::class, 'r', 'WITH', 'pu.role = r.identifier')
            ->leftJoin(Apartment::class, 'o', 'WITH', 'o.identifier = pu.object')
            ->leftJoin(Property::class, 'p', 'WITH', 'o.property = p.identifier')
            ->leftJoin(ObjectContracts::class, 'c', 'WITH', 'c.object = o.identifier')
            ->leftJoin(RentalTypes::class, 'rt', 'WITH', 'c.rentalType = rt.identifier')
            ->setParameter('deleted', false)
            ->andWhere('pu.user = :user')
            ->andWhere('p.user = :createdBy')
            ->setParameter('createdBy', $user)
            ->setParameter('user', $company);

        return $query->distinct()->getQuery()->getResult();
    }


    /**
     * returns all active users of an apartment
     *
     * @param int $apartment
     *
     * @return array
     */
    public function getActiveUsers(int $apartment): array
    {
        return $this->getActiveUsersByRole($apartment);
    }

    /**
     * returns all active users of an apartment
     *
     * @param array $parameters
     * @return array
     */
    public function getTenantsAndObjectOwners(array $parameters): array
    {
        $qb = $this->createQueryBuilder('pu')
            ->join('pu.role', 'r')
            ->where('pu.user = :user AND r.roleKey IN (:roles)')
            ->setParameters($parameters);
        return $qb->getQuery()->getResult();
    }

    /**
     *
     * @param ObjectContracts $contract
     * @return void
     */
    public function disableContractUsers(ObjectContracts $contract): void
    {
        $this->createQueryBuilder('pu')
            ->update()
            ->set('pu.isActive', ':inActive')
            ->where('pu.contract = :contract')
            ->setParameters(['inActive' => 0, 'contract' => $contract])
            ->getQuery()
            ->execute();

    }

    /**
     *
     * @param Apartment $apartment
     * @return int|mixed|string
     */
    public function deleteUsers(Apartment $apartment)
    {
        $qb = $this->createQueryBuilder('p');
        $query = $qb->update('App\Entity\PropertyUser', 'p')
            ->set('p.deleted', ':deleted')
            ->set('p.isActive', ':status')
            ->where('p.object = :object')
            ->setParameters(array('deleted' => true, 'object' => $apartment, 'status' => 0))
            ->getQuery();
        return $query->execute();
    }

    /**
     * returns all active user identifiers of apartments
     *
     * @param array $apartment
     *
     * @return array
     */
    public function getActiveContractorIdentifiers(array $apartment): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('IDENTITY (t.user) AS contractUsers')
            ->distinct()
            ->where('t.object IN (:apartment)')
            ->andWhere('t.isActive = :active AND t.deleted = false')
            ->setParameters(['apartment' => $apartment, 'active' => true]);

        return array_column($qb->getQuery()->getResult(), 'contractUsers');
    }

    /**
     * returns all active apartments
     *
     * @param UserIdentity $user
     * @return array
     */
    public function getActiveApartmentsOfUser(UserIdentity $user): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('IDENTITY (t.object) AS apartments')
            ->distinct()
            ->where('t.user IN (:user)')
            ->andWhere('t.isActive = :active AND t.deleted = false')
            ->setParameters(['user' => $user, 'active' => true]);

        return array_column($qb->getQuery()->getResult(), 'apartments');
    }

    /**
     * returns all property owners and admins
     *
     * @param UserIdentity $user
     * @return array
     */
    public function findPropertyOwnersAndAdmins(UserIdentity $user): array
    {
        $qb = $this->createQueryBuilder('pu')
            ->select('IDENTITY (p.user) AS owner, IDENTITY(p.administrator) AS administrator')
            ->distinct()
            ->join('pu.property', 'p')
            ->where('pu.user IN (:user)')
            ->andWhere('pu.isActive = :active AND pu.deleted = false')
            ->setParameters(['user' => $user->getIdentifier(), 'active' => true]);

        $owners = array_column($qb->getQuery()->getResult(), 'owner');
        $administrators = array_column($qb->getQuery()->getResult(), 'administrator');
        $result = array_merge($owners, $administrators);

        return array_values(array_unique(array_filter($result)));
    }

    /**
     * @param int $contractor
     * @return array
     */
    public function getContractorApartmentIdentifiers(int $contractor): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('IDENTITY (p.object)')
            ->where('p.user = :contractor AND p.deleted = :deleted AND p.deleted = :deleted AND p.isActive = :isActive')
            ->setParameters(['deleted' => false, 'contractor' => $contractor, 'isActive' => true]);

        return array_column($qb->distinct()->getQuery()->getResult(), 'identifier');
    }

    /**
     * getActiveContractorsOfProperty
     *
     * @param array $params
     * @return array
     */
    public function getActiveContractorsOfProperty(array $params): array
    {
        $params += ['deleted' => false, 'isActive' => true];
        $qb = $this->createQueryBuilder('p')
            ->select('IDENTITY (p.user) AS user, IDENTITY (o.contractType) AS contractType')
            ->join(ObjectContractDetail::class, 'o', 'WITH', 'o.object = p.object')
            ->where('p.property IN (:properties) AND p.deleted = :deleted AND p.isActive = :isActive')
            ->setParameters($params);

        $result = $qb->distinct()->getQuery()->getResult();
        $resultant = [];
        foreach ($result as $res) {
            $contractType = $this->_em->getRepository(ContractTypes::class)->findOneBy(['identifier' => $res['contractType']]);
            if ($contractType instanceof ContractTypes) {
                $resultant[$contractType->getNameEn()][] = $res['user'];
            }
        }
        return $resultant;
    }

    /**
     * getPinnedActiveContractorOfObject
     *
     * @param array $params
     * @return array
     */
    public function getPinnedActiveContractorOfObject(array $params): array
    {
        $params += ['deleted' => false, 'isActive' => true];
        $qb = $this->createQueryBuilder('p')
            ->select('u.identifier, u.firstName, u.lastName')
            ->join('p.user', 'u')
            ->where('p.object IN (:objects) AND p.deleted = :deleted AND p.isActive = :isActive AND p.isPinnedUser = :isActive')
            ->setParameters($params);
        return $qb->distinct()->getQuery()->getResult();
    }

    /**
     * getUserObjectAllocationsOfProperty
     *
     * @param int $property
     * @param int $user
     * @return array
     */
    public function getUserObjectAllocationsOfProperty(int $property, int $user): array
    {
        $params = ['property' => $property, 'user' => $user, 'deleted' => false, 'isActive' => true];
        $qb = $this->createQueryBuilder('p')
            ->select('o.name, o.identifier')
            ->join('p.object', 'o')
            ->where('p.property IN (:property) AND p.deleted = :deleted AND p.isActive = :isActive AND p.user = :user')
            ->setParameters($params);
        return $qb->distinct()->getQuery()->getResult();
    }

    /**
     * function to get people list from directory
     *
     * @param int $property
     * @param string|null $parameter
     * @return array
     */
    public function getPeopleList(int $property, ?string $parameter = null): array
    {
        $params = ['property' => $property, 'deleted' => false];
        $qb = $this->createQueryBuilder('p')
            ->select('p.identifier AS propertyUserIdentifier', 'p.publicId', 'IDENTITY(p.user) AS user', 'd.companyName', 'u.property', 'u.firstLogin', 'd.publicId AS directoryId', 'd.identifier',
                'ui.firstName', 'ui.lastName', 'p.isActive', 'd.firstName AS directoryFirstName', 'd.lastName AS directoryLastName',
                'd.street AS directoryStreet', 'd.streetNumber AS directoryStreetNumber', 'd.city AS directoryCity',
                'd.country AS directoryCountry', 'd.zipCode AS directoryZipCode', 'd.phone AS directoryPhone', 'p.isJanitor')
            ->join(Directory::class, 'd', 'WITH', 'd.property = p.property AND p.user = d.user')
            ->join('p.user', 'ui')
            ->join(User::class, 'u', 'WITH', 'u.identifier = ui.user')
            ->where('p.property = :property AND p.deleted = :deleted');
        if (!empty(trim($parameter))) {
            $qb->andWhere("ui.firstName LIKE :search OR ui.lastName LIKE :search OR u.property LIKE :search OR CONCAT(ui.firstName, ' ', ui.lastName) like :search 
            OR CONCAT(d.firstName, ' ', d.lastName) LIKE :search OR d.street LIKE :search OR d.streetNumber LIKE :search OR d.city LIKE :search 
            OR d.state LIKE :search OR d.country LIKE :search OR d.zipCode LIKE :search OR d.phone LIKE :search OR ui.companyName like :search");
            $params += ['search' => '%' . $parameter . '%'];
        }
        $qb->setParameters($params)
            ->groupBy('p.user');

        return $qb->getQuery()->getResult();
    }

    /**
     * function to get users and their roles in a property in particular an object
     *
     * @param array $params
     * @return array
     */
    public function getPeopleListMessageToSend(array $params): array
    {
        $params += ['deleted' => false, 'isActive' => true];

        $qb = $this->createQueryBuilder('p')
            ->select('u.identifier', 'r.roleKey AS userRole', 'pr.identifier AS property', 'IDENTITY(pr.user) AS owner',
                'IDENTITY(pr.administrator) AS administrator', 'IDENTITY(pr.janitor) AS janitor')
            ->join('p.property', 'pr')
            ->join('p.role', 'r')
            ->join('p.user', 'u')
            ->where('pr.identifier IN (:property) AND p.user IN (:user) AND p.isActive = :isActive 
                    AND p.object IN (:apartment) AND p.deleted = :deleted');
        if (isset($params['damage']) && $params['damage'] != '') {
            $qb->addSelect('IDENTITY(o.company) AS company')
                ->join('p.object', 'a')
                ->join(Damage::class, 'd', 'WITH', 'd.apartment = a.identifier')
                ->leftJoin(DamageOffer::class, 'o', 'WITH', 'o.damage = d.identifier')
                ->andWhere('o.damage = :damage AND o.acceptedDate IS NOT NULL');
        }
        $qb->setParameters($params);

        return $qb->distinct()->getQuery()->getResult();
    }

    /**
     * function to get users and their roles in a property in particular an object
     *
     * @param array $params
     * @return array
     */
    public function getPeopleRoleInProperty(array $params): array
    {
        $params += ['deleted' => false, 'isActive' => true];
        $qb = $this->createQueryBuilder('p')
            ->select('r.roleKey', 'IDENTITY(p.user) AS user')
            ->leftJoin('p.role', 'r')
            ->where('p.property IN (:property) AND p.user = :user AND p.deleted = :deleted 
                    AND p.isActive = :isActive AND p.object IN (:apartment)')
            ->setParameters($params);
        return $qb->getQuery()->getResult();
    }
}
