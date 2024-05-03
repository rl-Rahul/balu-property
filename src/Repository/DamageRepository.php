<?php

/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Repository;

use App\Entity\Address;
use App\Entity\ContractTypes;
use App\Entity\DamageRequest;
use App\Entity\DamageStatus;
use App\Entity\ObjectContractDetail;
use App\Entity\ObjectContracts;
use App\Entity\ObjectTypes;
use App\Entity\Property;
use App\Entity\User;
use App\Entity\UserIdentity;
use App\Entity\Damage;
use App\Service\DMSService;
use App\Service\ObjectService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Traits\ReadStatusTrait;
use App\Repository\Traits\RepositoryTrait;
use App\Entity\PropertyUser;
use Doctrine\ORM\QueryBuilder;
use App\Utils\Constants;
use App\Entity\Role;
use App\Entity\Apartment;
use App\Entity\DamageOffer;
use App\Entity\Message;
use App\Entity\DamageImage;
use App\Entity\DamageAppointment;
use App\Entity\DamageComment;
use App\Entity\DamageDefect;
use App\Entity\DamageLog;
use Google\Service\CloudSearch\UserId;
use App\Helpers\DamageQueryBuilderHelper;
use App\Helpers\DamageStatusHelper;

/**
 * DamageRepository
 * Repository used for user registration related queries
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class DamageRepository extends ServiceEntityRepository
{
    use ReadStatusTrait;
    use RepositoryTrait;

    /**
     * @var DMSService $dmsService
     */
    private DMSService $dmsService;

    /**
     * @var ObjectService $objectService
     */
    private ObjectService $objectService;

    /**
     * DamageRepository constructor.
     * @param ManagerRegistry $registry
     * @param DMSService $dmsService
     * @param ObjectService $objectService
     */
    public function __construct(ManagerRegistry $registry, DMSService $dmsService, ObjectService $objectService)
    {
        parent::__construct($registry, Damage::class);
        $this->dmsService = $dmsService;
        $this->objectService = $objectService;
    }

    /**
     * getAllDamages
     *
     * Function to get all damages created by tenant
     *
     * @param UserIdentity $user
     * @param string|null $currentRole
     * @param array|null $params
     * @param bool $countOnly
     * @param bool $isDashboard
     * @return array
     */
    public function getAllDamages(UserIdentity $user, string $currentRole, array $params = null, bool $countOnly = false, bool $isDashboard = false): array
    {
        $qb = $this->createQueryBuilder('d');
        $query = $qb->orderBy('d.identifier', 'DESC');
        $qb->join('d.user', 'u');
        $qb->join('d.apartment', 'a');
        $qb->join('a.property', 'p');
        $qb->join('d.status', 's');
        if (!empty($params['status']) && is_string($params['status']) && $params['status'] === 'open') {
            $qb->leftjoin('d.currentUser', 'cua');
        }
        $this->applyArchiveTaskCondition($qb, $user, $currentRole, $params);
        $andWhere = '';
        if (isset($params['apartment']) && $params['apartment'] !== false) {
            $qb->andWhere('d.apartment = :apartment')
                ->setParameter('apartment', $params['apartment']);
        }
        if (isset($params['property']) && $params['property'] !== false) {
            $qb->andWhere('p.identifier = :property')
                ->setParameter('property', $params['property']);
        }
        if (!empty($params['text'])) {
            $this->applyFreeTextFilter($params['text'], $qb);
        }
        if (!empty($params['assignedTo'])) {
            $qb->andWhere('d.assignedCompany = :assignedCompany')
                ->setParameter('assignedCompany', $params['assignedTo']);
        }
        if (!empty($params['preferredCompany'])) {
            $qb->andWhere('d.preferredCompany = :preferredCompany')
                ->setParameter('preferredCompany', $params['preferredCompany']);
        }
        if (!empty($params['limit'])) {
            $qb->setMaxResults($params['limit']);
        }
        if (!empty($params['offset'])) {
            $qb->setFirstResult($params['offset']);
        }
        if (in_array($currentRole, [$this->dmsService->convertCamelCaseString(Constants::COMPANY_ROLE), $this->dmsService->convertCamelCaseString(Constants::COMPANY_USER_ROLE)])) {
            $andWhere = ' and dr.company = :company';
            $company = ($currentRole == $this->dmsService->convertCamelCaseString(Constants::COMPANY_ROLE)) ? $user : $user->getParent();
            $qb->setParameter('company', $company);
        }
        $qb->andWhere('d.deleted = :deleted and p.deleted = :deleted and a.deleted = :deleted' . $andWhere)
            ->setParameter('deleted', false);
        if ($isDashboard) {
            $qb->andWhere('p.active = :active AND a.active = :active')
                ->setParameter('active', true);
        }
        if ($countOnly) {
            $qb->select('count(distinct d.identifier) as count');
            return $query->distinct()->getQuery()->getResult();
        }

        return $query->distinct()->getQuery()->getResult();
    }


    /**
     * getDamageIds
     *
     * Function to get all damage Ids
     *
     * @param array|null $params
     * @return array
     *
     */
    public function getDamageIds(array $params = null): array
    {
        $qb = $this->createQueryBuilder('d');
        $query = $qb->select('d.identifier');
        if (!empty($params['status'])) {
            $qb->innerJoin('DamageStatus', 's', 'WITH', 's.identifier=d.status')
                ->add('where', $qb->expr()->in('s.key', $params['status']));
        }
        if (!empty($params['apartment'])) {
            $qb->andWhere('d.apartment = :apartment')
                ->setParameter('apartment', $params['apartment']);
        }
        if (!empty($params['createdBy'])) {
            $qb->andWhere('d.user = :createdUser')
                ->setParameter('createdUser', $params['createdBy']);
        }
        $qb->andWhere('d.deleted != 1 ');
        return array_column($query->getQuery()->getResult(), "identifier");
    }

    /**
     * getDamageCount
     *
     * Function to get damage count based on a property
     *
     * @param int property
     * @param array $statusArray
     *
     * @return int|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getDamageCount(int $property, array $statusArray): ?int
    {
        $qb = $this->createQueryBuilder('d');
        $query = $qb->select('COUNT(d.identifier)')
            //->leftJoin('j.jobDetails', 's')
            ->leftJoin('App\Entity\Apartment', 'a', 'WITH', 'a.identifier = d.apartment')
            ->leftJoin('App\Entity\Property', 'p', 'WITH', 'p.identifier = a.property')
            ->leftJoin('App\Entity\DamageStatus', 's', 'WITH', 's.identifier = d.status')
            ->where('p.identifier = :property')
            ->andWhere('s.key IN (:statusArray)')
            ->setParameters(['property' => $property, 'statusArray' => $statusArray]);

        return $query->getQuery()->getSingleScalarResult();
    }


    /**
     * close all damages
     *
     * @param Apartment $apartment
     * @return void
     */
    public function deleteDamage(Apartment $apartment)
    {
        $ids = $this->createQueryBuilder('d')
            ->join(Apartment::class, 'a', 'WITH', 'a.identifier = d.apartment')
            ->where('d.apartment = :apartment')
            ->select('d.identifier')
            ->setParameters(array('apartment' => $apartment))
            ->getQuery()->getResult();

        $qb = $this->createQueryBuilder('d');
        $query = $qb->update('App\Entity\Damage', 'd')
            ->set('d.deleted', ':deleted')
            ->where('d.identifier in (:ids)')
            ->setParameters(array('deleted' => true, 'ids' => $ids))
            ->getQuery();
        $query->execute();

        $this->getEntityManager()->getRepository(DamageOffer::class)->deleteDamageOffers($ids);
        $this->getEntityManager()->getRepository(Message::class)->deleteMessages($ids);
        $this->getEntityManager()->getRepository(DamageImage::class)->deleteDamageImages($ids);
        $this->getEntityManager()->getRepository(DamageAppointment::class)->deleteDamageAppointments($ids);
        $this->getEntityManager()->getRepository(DamageComment::class)->deleteComments($ids);
        $this->getEntityManager()->getRepository(DamageDefect::class)->deleteDefects($ids);
        $this->getEntityManager()->getRepository(DamageLog::class)->deleteLogs($ids);
    }

    /**
     * delete offers
     *
     * @param array $damages
     * @return void
     */
    public function closeOffers(array $damages)
    {
        $qb = $this->createQueryBuilder('d');
        $query = $qb->update('DamageOffer', 'd')
            ->set('d.active', ':active')
            ->where('d.damage IN (:damage)')
            ->setParameters(array('active' => true, 'damage' => $damages))
            ->getQuery();
        return $query->execute();
    }

    /**
     * pendingRepairConfirmationDamages
     *
     * @param int $days
     * @param string $status
     * @return void
     */
    public function pendingRepairConfirmationDamages(int $days, string $status)
    {
        $qb = $this->createQueryBuilder('d');
        $curDate = new \DateTime('now');
        $query = $qb->innerJoin(DamageAppointment::class, 'a', 'WITH', 'd.identifier = a.damage')
            ->innerJoin(DamageStatus::class, 's', 'WITH', 's.identifier = d.status')
            ->where('d.deleted = :deleted')
            ->andWhere('DATE_DIFF(:curDate,a.scheduledTime) BETWEEN :min AND :max')
            ->andWhere('s.key != :status')
            ->setParameters(array('deleted' => 0, 'status' => $status, 'max' => $days, 'min' => 1, 'curDate' => $curDate));
        return $query->getQuery()->execute();
    }

    /**
     * getDamageDetails
     *
     * Function to get details of a damage ticket
     *
     * @param string $damage
     *
     * @return array
     *
     * @throws NonUniqueResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getDamageDetails(string $damage): ?array
    {
        $qb = $this->createQueryBuilder('d');
        $query = $qb->select('d.publicId', 'd.title', 'd.description', 'd.isDeviceAffected', 'c.publicId as preferredCompany', 'a.publicId as apartment',
            's.key as status', 'd.barCode', 'd.identifier')
            ->leftJoin('d.preferredCompany', 'c')
            ->innerJoin('d.apartment', 'a')
            ->innerJoin('d.status', 's')
            ->where('d.publicId = :damage')
            ->setParameter('damage', $damage, 'uuid')
            ->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * applyFreeTextFilter
     *
     * Function apply free text filter
     *
     * @param string $text
     * @param QueryBuilder $query
     *
     * @return QueryBuilder
     */
    private function applyFreeTextFilter(string $text, QueryBuilder $query): QueryBuilder
    {
        $idSearch = $this->searchWithId($query, $text, 'd.identifier');
        if (null !== $idSearch) {
            return $idSearch;
        }

        return $query->andWhere('REGEXP(d.identifier, d.title, a.name, p.address, u.firstName, u.lastName, :pattern) = true')
            ->setParameter('pattern', $this->generateRegex($text));
    }

    /**
     * Applies archive task condition to the query builder based on the user's role and parameters.
     *
     * @param QueryBuilder $qb
     * @param UserIdentity $user
     * @param string $currentRole
     * @param array $params
     */
    private function applyArchiveTaskCondition(QueryBuilder $qb, UserIdentity $user, string $currentRole, array $params = []): void
    {
        $dmsService = $this->dmsService;
        $em = $this->_em;
        $convertedRole = $dmsService->convertSnakeCaseString($currentRole);

        switch ($convertedRole) {
            case Constants::PROPERTY_ADMIN_ROLE:
                DamageQueryBuilderHelper::applyAdminCondition($qb, $user, $em);
                break;
            case Constants::OWNER_ROLE:
                DamageQueryBuilderHelper::applyOwnerCondition($qb, $user, $em);
                break;
            case Constants::JANITOR_ROLE:
                DamageQueryBuilderHelper::applyJanitorCondition($qb, $user, $em);
                break;
            case Constants::COMPANY_ROLE:
                DamageQueryBuilderHelper::applyCompanyCondition($qb, $user, $params);
                break;
            case Constants::COMPANY_USER_ROLE:
                DamageQueryBuilderHelper::applyCompanyUserCondition($qb, $user, $params);
                break;
            default:
                DamageQueryBuilderHelper::applyTenantAndObjectOwnerCondition($qb, $user, $currentRole, $params);
                break;
        }

        $damageStatuses = isset($params['status']) && $params['status'] == 'open'
            ? DamageStatusHelper::getOpenDamageStatuses($qb, $params, $convertedRole)
            : DamageStatusHelper::getCloseDamageStatuses($qb, $params, $convertedRole);

        // Set damage statuses parameter
        $qb->setParameter('status', $damageStatuses);
    }

    /**
     *
     * @param UserIdentity $user
     * @param Role $role
     * @return array
     */
    public function getTicketCount(UserIdentity $user, Role $role): array
    {
        $condition = '';
        $param = [];
        $qb = $this->createQueryBuilder('d')
            ->select('count(distinct d.identifier) as count')
            ->join('d.status', 's')
            ->join('d.apartment', 'a')
            ->join('a.property', 'p')
            ->leftJoin('d.currentUserRole', 'r');
        $roleKey = $this->dmsService->convertSnakeCaseString($role->getRoleKey());
        if ($roleKey === Constants::COMPANY_ROLE || $roleKey === Constants::COMPANY_USER_ROLE) {
            $qb->join(DamageRequest::class, 'dr', 'WITH', 'dr.damage = d.identifier')
                ->join(DamageStatus::class, 'ds', 'WITH', 'dr.status = ds.identifier');
            $condition = 'OR (dr.company = :currentUser AND ds.key IN (:companyStatus))';
            $user = ($roleKey === Constants::COMPANY_USER_ROLE) ? $user->getParent() : $user;
            $param += ['companyStatus' => Constants::COUNT_OPEN_DAMAGES_FOR_COMPANY_AND_COMPANY_USER_ROLE];
        } elseif ($roleKey === Constants::PROPERTY_ADMIN_ROLE) {
            $condition = ' AND s.key NOT LIKE :adminRejStatus OR (p.administrator = :currentUser AND (s.key LIKE :adminstatus OR s.key LIKE :rejectStatus OR (s.key LIKE :companyStatus AND (r.roleKey <> :obj_owner OR r.roleKey <> :tenant))))';
            $param += ['adminRejStatus' => 'PROPERTY_ADMIN_REJECT_DAMAGE', 'adminstatus' => '%PROPERTY_ADMIN_ACCEPTS_THE_OFFER%', 'rejectStatus' => '%COMPANY_REJECT_THE_DAMAGE%', 'companyStatus' => '%COMPANY_SCHEDULE_DATE%', 'obj_owner' => 'object_owner', 'tenant' => 'tenant'];
        } elseif ($roleKey === Constants::OWNER_ROLE) {
            $condition = ' AND s.key NOT LIKE :adminRejStatus OR (p.user = :currentUser AND s.key LIKE :ownerStatus) OR (p.user = :currentUser AND s.key LIKE :adminstatus )';
            $param += ['adminRejStatus' => 'PROPERTY_ADMIN_REJECT_DAMAGE', 'ownerStatus' => '%OWNER_ACCEPTS_THE_OFFER%', 'adminstatus' => '%PROPERTY_ADMIN_ACCEPTS_THE_OFFER%'];
        } elseif ($roleKey === Constants::TENANT_ROLE) {
            $condition = ' AND s.key NOT LIKE :tenantRejStatus OR (d.companyAssignedBy = :currentUser AND s.key LIKE :tenantStatus)';
            $param += ['tenantRejStatus' => 'TENANT_REJECT_DAMAGE', 'tenantStatus' => '%TENANT_ACCEPTS_THE_OFFER%'];
            $condition .= ' OR (d.assignedCompany = :currentUser AND s.key LIKE :adminDamageStatus)';
            $param += ['adminDamageStatus' => '%PROPERTY_ADMIN_REJECT_DAMAGE%'];
        } elseif ($roleKey === Constants::OBJECT_OWNER_ROLE) {
            $condition = ' AND s.key NOT LIKE :objOwnerRejStatus OR (d.companyAssignedBy = :currentUser AND s.key LIKE :tenantStatus)';
            $param += ['objOwnerRejStatus' => 'OBJECT_OWNER_REJECT_DAMAGE', 'tenantStatus' => '%OBJECT_OWNER_ACCEPTS_THE_OFFER%'];
            $condition .= ' OR (d.assignedCompany = :currentUser AND s.key LIKE :adminDamageStatus)';
            $param += ['adminDamageStatus' => '%PROPERTY_ADMIN_REJECT_DAMAGE%'];
        } elseif ($roleKey === Constants::JANITOR_ROLE) {
            $condition .= ' AND s.key NOT LIKE :adminDamageStatus';
            $param += ['adminDamageStatus' => '%PROPERTY_ADMIN_REJECT_DAMAGE%'];
        }

        $param += ['currentUser' => $user, 'role' => $role, 'deleted' => false,
            'confirmStatus' => '%confirmed%', 'closeStatus' => '%close%', 'active' => true];
        $qb->where("(d.currentUser = :currentUser AND d.currentUserRole = :role) $condition")
            ->andWhere('p.active = :active AND s.status NOT like :confirmStatus AND s.status NOT like :closeStatus')
            ->andWhere('d.deleted = :deleted')
            ->setParameters($param);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $damage
     * @param UserIdentity|null $user
     * @return int|mixed|string
     */
    public function getAllOfferAndRequests(string $damage, ?UserIdentity $user = null)
    {
        $params = ['damage' => $damage, 'deleted' => 0, 'active' => true];
        $qb = $this->createQueryBuilder('d')
            ->select('o.publicId as offer, ui.publicId as company,
             ui.firstName, ui.lastName, ui.companyName, u.property as email, a.phone, a.landLine, a.street, r.publicId as request,
             a.streetNumber, a.city, a.zipCode, a.state, a.country, a.countryCode, o.amount, o.description as offerDescription,
             o.acceptedDate AS accepted, IDENTITY(o.attachment) as attachment, o.priceSplit, IDENTITY(r.status) as status,
             o.active as activeOffer, r.comment, r.companyEmail AS companyEmail')
            ->join(DamageRequest::class, 'r', 'WITH', 'r.damage = d.identifier')
            ->leftJoin(DamageOffer::class, 'o', 'WITH', 'o.damageRequest = r.identifier AND o.active = :active')
            ->join(UserIdentity::class, 'ui', 'WITH', 'ui.identifier = r.company')
            ->join(User::class, 'u', 'WITH', 'u.identifier = ui.user OR u.property = r.companyEmail')
            ->join(Address::class, 'a', 'WITH', 'a.user = ui.identifier')
            ->where('d.identifier = :damage')
            ->andWhere('r.deleted = :deleted AND r.newOfferRequestedDate IS NULL');
        if ($user instanceof UserIdentity) {
            $qb->andWhere('r.company = :company');
            $params += ['company' => $user->getIdentifier()];
        }
        // $qb->groupBy('ui.identifier')
        $qb->groupBy('o.publicId', 'ui.identifier', 'ui.publicId', 'ui.firstName', 'ui.lastName', 'ui.companyName', 'u.property', 'a.phone', 'a.landLine', 'a.street', 'r.publicId', 'a.streetNumber', 'a.city', 'a.zipCode', 'a.state', 'a.country', 'a.countryCode', 'o.amount', 'o.description', 'o.acceptedDate', 'o.priceSplit', 'r.comment', 'r.companyEmail', 'r.createdAt') // Group by all selected non-aggregated columns
        ->setParameters($params)
            ->orderBy('r.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * getUserBasedTickets
     *
     * @param UserIdentity $user
     * @param string $currentRole
     * @param array $damages
     * @return array
     * @throws \Exception
     */
    protected function getRoleBasedTickets(UserIdentity $user, string $currentRole, array $damages = []): array
    {
        $currentRole = $this->dmsService->convertSnakeCaseString($currentRole);
        $em = $this->_em;
        switch ($currentRole) {
            case Constants::OWNER_ROLE:
                $properties = $em->getRepository(Property::class)->findProperties(['user' => $user->getIdentifier()]);
                $activeContractors = $em->getRepository(PropertyUser::class)->getActiveContractorsOfProperty(['properties' => $properties]);
                $ownerTickets = $this->getOwnerTicketCount($user, $damages);
                $companyTickets = $this->getCompanyTicketCount($user, $damages, 3);

                $data[Constants::OWNER_ROLE] = count($ownerTickets);
                $data[Constants::COMPANY_ROLE] = count($companyTickets);
                if (isset($activeContractors['Ownership']) && count($activeContractors['Ownership']) > 0) {
                    $objectOwnerTickets = $this->getObjectOwnerTicketCount($user, $ownerTickets);
                    $data[$this->dmsService->convertCamelCaseString(Constants::OBJECT_OWNER_ROLE)] = count($objectOwnerTickets);
                }
                if (isset($activeContractors['Rental']) && count($activeContractors['Rental']) > 0) {
                    $tenantTickets = $this->getTenantTicketCount($user, $ownerTickets);
                    $data[Constants::TENANT_ROLE] = count($tenantTickets);
                }
                break;
            case Constants::PROPERTY_ADMIN_ROLE:
                $propertyAdminRole = $this->dmsService->convertCamelCaseString(Constants::PROPERTY_ADMIN_ROLE);
                $properties = $em->getRepository(Property::class)->findProperties(['user' => $user->getIdentifier()], $propertyAdminRole);
                $activeContractors = $em->getRepository(PropertyUser::class)->getActiveContractorsOfProperty(['properties' => $properties]);
                $adminTickets = $this->getAdminTicketCount($user, $damages);
                $companyTickets = $this->getCompanyTicketCount($user, $adminTickets, 3);

                $data[Constants::COMPANY_ROLE] = count($companyTickets);
                $data[$propertyAdminRole] = count($adminTickets);
                if (isset($activeContractors['Ownership']) && count($activeContractors['Ownership']) > 0) {
                    $objectOwnerTickets = $this->getObjectOwnerTicketCount($user, $damages);
                    $data[$this->dmsService->convertCamelCaseString(Constants::OBJECT_OWNER_ROLE)] = count($objectOwnerTickets);
                }
                if (isset($activeContractors['Rental']) && count($activeContractors['Rental']) > 0) {
                    $tenantTickets = $this->getTenantTicketCount($user, $adminTickets);
                    $data[Constants::TENANT_ROLE] = count($tenantTickets);
                }
                break;
            case Constants::TENANT_ROLE:
                $tenantTickets = $this->getTenantTicketCount($user, $damages);
                $ownerTickets = $this->getOwnerTicketCount($user, $damages, 2);
                $companyTicketCount = $this->getCompanyTicketCount($user, $damages, 2);

                $data[Constants::OWNER_ROLE] = count($ownerTickets);
                $data[Constants::TENANT_ROLE] = count($tenantTickets);
                $data[Constants::COMPANY_ROLE] = count($companyTicketCount);
                break;
            case Constants::OBJECT_OWNER_ROLE:
                $objectOwnerTickets = $this->getObjectOwnerTicketCount($user, $damages);
                $ownerTickets = $this->getOwnerTicketCount($user, $damages, 2);
                $companyTickets = $this->getCompanyTicketCount($user, $damages, 2);

                $data[Constants::OWNER_ROLE] = count($ownerTickets);
                $data[Constants::TENANT_ROLE] = count($objectOwnerTickets);
                $data[Constants::COMPANY_ROLE] = count($companyTickets);
                break;
            case Constants::COMPANY_ROLE:
            case Constants::COMPANY_USER_ROLE:
                $companyTickets = $this->getCompanyTicketCount($user, $damages);
                $tenantTickets = $this->getTenantTicketCount($user, $damages);
                $ownerTickets = $this->getOwnerTicketCount($user, $damages, 3);

                $data[Constants::OWNER_ROLE] = count($ownerTickets);
                $data[Constants::TENANT_ROLE] = count($tenantTickets);
                $data[Constants::COMPANY_ROLE] = count($companyTickets);
                break;
            default:
                $data[Constants::OWNER_ROLE] = 0;
                $data[Constants::TENANT_ROLE] = 0;
                $data[Constants::COMPANY_ROLE] = 0;
                $data[$this->dmsService->convertCamelCaseString(Constants::PROPERTY_ADMIN_ROLE)] = 0;
                $data[$this->dmsService->convertCamelCaseString(Constants::OBJECT_OWNER_ROLE)] = 0;

        }
        return $data;
    }

    /**
     * getDamages
     *
     * @param UserIdentity $user
     * @param string $currentRole
     * @return int|mixed|string
     */
    protected function getDamages(UserIdentity $user, string $currentRole): array
    {
        $currentRole = $this->dmsService->convertSnakeCaseString($currentRole);
        if ($currentRole === Constants::OWNER_ROLE) {
            $users = $this->_em->getRepository(Property::class)->findPropertyAdmins($user->getIdentifier());
            array_push($users, $user->getIdentifier());
            $apartments = $this->_em->getRepository(Apartment::class)->getOwnerApartmentIdentifiers($user->getIdentifier(), $users);
            $contractUsers = $this->objectService->getContractUsers($apartments);
            $users = array_merge($users, $contractUsers);
        } else if ($currentRole === Constants::PROPERTY_ADMIN_ROLE) {
            $users = $this->_em->getRepository(Property::class)->findPropertyOwners($user->getIdentifier());
            array_push($users, $user->getIdentifier());
            $apartments = $this->_em->getRepository(Apartment::class)->getAdminApartmentIdentifiers($user->getIdentifier(), $users);
            $contractUsers = $this->objectService->getContractUsers($apartments);
            $users = array_merge($users, $contractUsers);
        } else {
            $apartments = $this->_em->getRepository(PropertyUser::class)->getContractorApartmentIdentifiers($user->getIdentifier());
            $users = $this->objectService->getContractUsers($apartments);
        }

        $qb = $this->createQueryBuilder('d')
            ->select('d.identifier')
            ->join('d.apartment', 'a')
            ->join('a.property', 'p')
            ->join('d.status', 's');
        if ($currentRole === Constants::COMPANY_USER_ROLE || $currentRole === Constants::COMPANY_ROLE) {
            $users[] = $user->getIdentifier();
            if ($currentRole === Constants::COMPANY_USER_ROLE) {
                $users[] = $user->getParent()->getIdentifier();
            }
            $qb->join(DamageRequest::class, 'r', 'WITH', 'r.damage = d.identifier')
                ->where('d.deleted = :deleted AND p.active = :active AND a.active = :active AND r.company IN (:users) AND s.key NOT IN (:closedStatus)')
                ->setParameters(['users' => array_values($users), 'deleted' => false, 'active' => true, 'closedStatus' => Constants::CLOSE_DAMAGES]);
        } else {
            $qb->where('d.deleted = :deleted AND p.active = :active AND a.active = :active
                        AND (d.user IN (:users) OR d.damageOwner IN (:users)) AND s.key NOT IN (:closedStatus)')
                ->setParameters(['users' => array_values($users), 'deleted' => false, 'active' => true, 'closedStatus' => Constants::CLOSE_DAMAGES]);
        }

        return array_column($qb->distinct()->getQuery()->getResult(), 'identifier');
    }

    /**
     * getOwnerTicketCount
     *
     * @param UserIdentity $user
     * @param array $damages
     * @param int $case
     * @return array
     * @throws \Exception
     */
    protected function getOwnerTicketCount(UserIdentity $user, array $damages = [], int $case = 1): array
    {
        $users = $this->_em->getRepository(Property::class)->findPropertyAdmins($user->getIdentifier());
        array_push($users, $user->getIdentifier());
        $apartments = $this->_em->getRepository(Apartment::class)->getOwnerApartmentIdentifiers($user->getIdentifier(), $users);
        return $this->getOwnerOrAdminTicketCount($user, $users, $damages, $apartments, $case);
    }

    /**
     * getAdminTicketCount
     *
     * @param UserIdentity $user
     * @param array $damages
     * @param int $case
     * @return array
     * @throws \Exception
     */
    protected function getAdminTicketCount(UserIdentity $user, array $damages = [], int $case = 1): array
    {
        $users = $this->_em->getRepository(Property::class)->findPropertyOwners($user->getIdentifier());
        array_push($users, $user->getIdentifier());
        $apartments = $this->_em->getRepository(Apartment::class)->getAdminApartmentIdentifiers($user->getIdentifier(), $users);
        return $this->getOwnerOrAdminTicketCount($user, $users, $damages, $apartments, $case);
    }

    /**
     * getTenantTicketCount
     *
     * @param UserIdentity $user
     * @param array $damages
     * @return array
     */
    protected function getTenantTicketCount(UserIdentity $user, array $damages = []): array
    {
        $apartments = $this->_em->getRepository(PropertyUser::class)->getActiveApartmentsOfUser($user);
        return $this->getTenantOrObjectOwnerTicketCount($apartments, $damages);
    }

    /**
     * getObjectOwnerTicketCount
     *
     * @param UserIdentity $user
     * @param array $damages
     * @return array
     */
    protected function getObjectOwnerTicketCount(UserIdentity $user, array $damages = []): array
    {
        $apartments = $this->_em->getRepository(PropertyUser::class)->getActiveApartmentsOfUser($user);
        return $this->getTenantOrObjectOwnerTicketCount($apartments, $damages, true);
    }

    /**
     * getCompanyTicketCount
     *
     * @param UserIdentity $user
     * @param array $damages
     * @param int $case
     * @return array
     * @throws \Exception
     */
    protected function getCompanyTicketCount(UserIdentity $user, array $damages = [], int $case = 1): array
    {
        return $this->getTicketCountForCompanies($user, $damages, $case);
    }

    /**
     * getOwnerOrAdminTicketCount
     * $case = 1 => self
     * $case = 2 => tenant/object owner
     * $case = 3 => company
     * @param UserIdentity $user
     * @param array $users
     * @param array $damages
     * @param array $apartments
     * @param int $case
     * @return array
     * @throws \Exception
     */
    protected function getOwnerOrAdminTicketCount(UserIdentity $user, array $users, array $damages, array $apartments, int $case = 1): array
    {
        $contractUsers = $this->objectService->getContractUsers($apartments);
        $params = ['deleted' => false];
        array_push($users, $user->getIdentifier());
        $qb = $this->createQueryBuilder('d')
            ->select('d.identifier', 's.key', 'IDENTITY(d.apartment) AS apartment', 'IDENTITY(d.damageOwner) AS damageOwner')
            ->join('d.apartment', 'a')
            ->join('a.property', 'p')
            ->join('d.status', 's');
        switch ($case) {
            case 1:
                $qb->leftJoin(DamageRequest::class, 'dr', 'WITH', 'dr.damage = d.identifier')
                    ->leftJoin('dr.status', 'ds');
                $where = "AND (s.key IN (:assignedStatus) AND d.user IN (:contractUsers) AND d.allocation = :allocation AND a.active = :active AND p.active = :active AND dr.newOfferRequestedDate IS NULL) 
                OR (( s.key IN (:statuses) AND (d.damageOwner IN (:users) OR d.user IN (:users)) AND a.active = :active AND p.active = :active AND dr.newOfferRequestedDate IS NULL) 
                OR (ds.key IN (:addressableStatus)) AND ((d.damageOwner IN (:users) OR d.user IN (:users)) AND a.active = :active AND p.active = :active AND dr.newOfferRequestedDate IS NULL))";
                $params += [
                    'addressableStatus' => Constants::ADDRESSABLE_REQUEST_STATUES,
                    'deleted' => false,
                    'contractUsers' => $contractUsers,
                    'statuses' => Constants::ADDRESSABLE_DAMAGE_FOR_OWNER_OR_ADMIN,
                    'assignedStatus' => Constants::ADDRESSABLE_DAMAGE_FOR_OWNER_OR_ADMIN_FROM_OBJECT_OWNER_OR_TENANT,
                    'active' => true,
                    'users' => $users,
                    'allocation' => true
                ];
                break;
            case 2:
                $where = "AND d.identifier IN (:damages) AND s.key IN (:ownerResponsibleStatus) AND d.allocation = :allocation";
                $params += [
                    'ownerResponsibleStatus' => Constants::TENANT_CREATED_OWNER_RESPONSIBLE_STATUS,
                    'damages' => $damages,
                    'allocation' => true
                ];
                break;
            case 3:
                $qb->leftJoin(DamageRequest::class, 'dr', 'WITH', 'dr.damage = d.identifier')
                    ->leftJoin('dr.status', 'ds');
                $where = "AND d.identifier IN (:damages) AND ds.key IN (:statuses) AND dr.newOfferRequestedDate IS NULL";
                $params += [
                    'statuses' => Constants::OPEN_ADDRESSABLE_DAMAGES_FOR_OWNER_AND_ADMIN,
                    'damages' => $damages
                ];
                break;
            default:
                throw new \Exception('inValidCase');
        }
        $qb->where('d.deleted = :deleted ' . $where)
            ->groupBy('d.identifier')
            ->setParameters($params);

        return array_column($qb->distinct()->getQuery()->getResult(), 'identifier');
    }

    /**
     * getTenantOrObjectOwnerTicketCount
     *
     * @param array $apartments
     * @param array $damages
     * @param bool $isObjectOwner
     * @return array
     */
    protected function getTenantOrObjectOwnerTicketCount(array $apartments, array $damages = [], bool $isObjectOwner = false): array
    {
        if ($isObjectOwner) {
            $statuses = Constants::ADDRESSABLE_DAMAGE_FOR_OBJECT_OWNER;
            $contractType = $this->_em->getRepository(ContractTypes::class)->findOneBy(['nameEn' => Constants::CONTRACT_TYPE_OWNERSHIP]);
        } else {
            $statuses = Constants::ADDRESSABLE_DAMAGE_FOR_TENANT;
            $contractType = $this->_em->getRepository(ContractTypes::class)->findOneBy(['nameEn' => Constants::CONTRACT_TYPE_RENTAL]);
        }
        $contractUsers = $this->objectService->getContractUsers($apartments);
        $params = [
            'higherAcceptance' => Constants::ADDRESSABLE_OWNER_OR_ADMIN_ACCEPTANCE,
            'deleted' => false,
            'contractUsers' => $contractUsers,
            'statuses' => $statuses,
            'allocation' => false,
            'ownerAllocated' => true,
            'apartments' => $apartments,
            'contractType' => $contractType
        ];
        $qb = $this->createQueryBuilder('d')
            ->select('d.identifier', 's.key', 'IDENTITY(d.apartment) AS apartment', 'IDENTITY(d.damageOwner) AS damageOwner')
            ->join('d.status', 's')
            ->join(ObjectContractDetail::class, 'ocd', 'WITH', 'd.apartment = ocd.object')
            ->leftJoin(DamageRequest::class, 'dr', 'WITH', 'dr.damage = d.identifier')
            ->leftJoin('dr.status', 'ds')
            ->where('ocd.contractType = :contractType AND d.deleted = :deleted AND
                ((s.key IN (:statuses) AND d.user IN (:contractUsers) AND d.allocation = :allocation AND d.apartment IN (:apartments))
                OR (d.allocation = :ownerAllocated AND ds.key IN (:higherAcceptance) AND d.apartment IN (:apartments)))')
            ->setParameters($params);

        return array_column($qb->getQuery()->getResult(), 'identifier');
    }

    /**
     * getTicketCountForCompanies
     *
     * $case = 1 => self
     * $case = 2 => tenant/object owner
     * $case = 3 => company
     *
     * @param UserIdentity $user
     * @param array $damages
     * @param int $case
     * @return array
     * @throws \Exception
     */
    protected function getTicketCountForCompanies(UserIdentity $user, array $damages = [], int $case = 1): array
    {
        $params = ['deleted' => false, 'active' => true, 'companyStatus' => Constants::ADDRESSABLE_DAMAGE_FOR_COMPANIES_AGAINST_OFFER];
        $qb = $this->createQueryBuilder('d')
            ->select('d.identifier', 'IDENTITY(r.damage) as damageIdentifier')
            ->join('d.apartment', 'a')
            ->join('a.property', 'p')
            ->leftJoin(DamageRequest::class, 'r', 'WITH', 'r.damage = d.identifier')
            ->leftJoin(DamageOffer::class, 'o', 'WITH', 'r.identifier = o.damageRequest')
            ->leftJoin('r.status', 'drs')
            ->leftJoin('d.status', 's')
            ->where('d.deleted = :deleted AND drs.key IN (:companyStatus) AND a.active = :active AND p.active = :active');
        switch ($case) {
            case 1:
                $companies[] = $user->getIdentifier();
                if ($user->getParent() instanceof UserIdentity) {
                    $companies[] = $user->getParent()->getIdentifier();
                }
                $qb->andWhere('r.company IN (:company)');
                $params += ['company' => $companies];
                break;
            case 2:
            case 3:
                $qb->andWhere('d.identifier IN (:damages) AND NOT EXISTS (
                    SELECT 1 FROM App\Entity\DamageRequest dr1
                    INNER JOIN App\Entity\DamageStatus drs1 WITH dr1.status = drs1.identifier
                    WHERE dr1.damage = d.identifier AND drs1.key IN (:openDamages) AND dr1.newOfferRequestedDate IS NULL)
                ');
                $params += ['damages' => $damages, 'openDamages' => Constants::DAMAGES_TO_BE_ADDRESSED_BY_OWNER_OR_ADMIN_ON_COMPANY_ACTION];
                break;
            default:
                throw new \Exception('inValidCase');
        }
        $qb->setParameters($params);

        return array_column($qb->distinct()->getQuery()->getResult(), 'identifier');
    }
}