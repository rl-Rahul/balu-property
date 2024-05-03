<?php

namespace App\Repository;

use App\Entity\Address;
use App\Entity\Category;
use App\Entity\DamageRequest;
use App\Entity\Directory;
use App\Entity\Document;
use App\Entity\Permission;
use App\Entity\Property;
use App\Entity\PropertyUser;
use App\Entity\Role;
use App\Entity\Temp;
use App\Entity\User;
use App\Entity\UserDevice;
use App\Entity\UserIdentity;
use App\Entity\FavouriteIndividual;
use App\Entity\FavouriteCompany;
use App\Entity\FavouriteAdmin;
use App\Entity\UserPermissions;
use App\Service\DMSService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Traits\RepositoryTrait;
use App\Entity\Damage;
use App\Entity\DamageStatus;
use Doctrine\ORM\Query\Expr;
use App\Utils\Constants;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @method UserIdentity|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserIdentity|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserIdentity[]    findAll()
 * @method UserIdentity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserIdentityRepository extends ServiceEntityRepository
{
    use RepositoryTrait;

    /**
     * @var DMSService $dmsService
     */
    private DMSService $dmsService;

    /**
     * @var RequestStack $requestStack
     */
    protected RequestStack $requestStack;

    /**
     * UserIdentityRepository constructor.
     * @param ManagerRegistry $registry
     * @param DMSService $dmsService
     * @param RequestStack $requestStack
     */
    public function __construct(ManagerRegistry $registry, DMSService $dmsService, RequestStack $requestStack)
    {
        parent::__construct($registry, UserIdentity::class);
        $this->dmsService = $dmsService;
        $this->requestStack = $requestStack;
    }

    /**
     * @param array $entries
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateIdentitySelfJoinValues(array $entries): void
    {
        foreach ($entries as $entry) {
            $userIdentity = $this->getCurrentRespectiveUserFromTemp($entry['id']);
            if ($userIdentity instanceof UserIdentity) {
                if (isset($entry['created_by']) && $entry['created_by'] != '') {
                    $userIdentity->setCreatedBy($this->getCurrentRespectiveUserFromTemp($entry['created_by']));
                }
                if (isset($entry['administrator']) && $entry['administrator'] != '') {
                    $userIdentity->setAdministrator($this->getCurrentRespectiveUserFromTemp($entry['administrator']));
                }
                if (isset($entry['parent_id']) && $entry['parent_id'] != '') {
                    $userIdentity->setParent($this->getCurrentRespectiveUserFromTemp($entry['parent_id']));
                }
            }
        }
        $this->_em->flush();
    }

    /**
     * @param int $oldUser
     * @return UserIdentity|null
     */
    public function getCurrentRespectiveUserFromTemp(int $oldUser): ?UserIdentity
    {
        $tempEntity = $this->_em->getRepository(Temp::class)->findOneBy(['oldUserId' => $oldUser]);
        if (!$tempEntity instanceof Temp && !$tempEntity->getUser() instanceof User) {
            return null;
        }
        return $this->_em->getRepository(UserIdentity::class)->findOneBy(['user' => $tempEntity->getUser()]);
    }

    /**
     * @param array $userPermissions
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function migrateUserPermissions(array $userPermissions): void
    {
        foreach ($userPermissions as $entry) {
            $userPermission = new UserPermissions();
            $userPermission->setUser($this->getCurrentRespectiveUserFromTemp($entry['user_id']));
            $role = $this->_em->getRepository(Role::class)->findOneBy(['id' => $entry['role_id']]);
            $userPermission->setRole($role);
            $permission = $this->_em->getRepository(Permission::class)->findOneBy(['permissionKey' => $entry['permissionKey']]);
            $userPermission->setPermission($permission);
            $userPermission->setIsCompany($entry['is_company']);
            $userPermission->setCreatedAt(new \DateTime($entry['created_on']));
            if ($entry['updated_on'] != null) {
                $userPermission->setUpdatedAt(new \DateTime($entry['updated_on']));
            }
            $this->_em->persist($userPermission);
        }
        $this->_em->flush();
    }

    /**
     * @param array $userDevices
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function migrateUserDevices(array $userDevices): void
    {
        foreach ($userDevices as $device) {
            $userDevice = new UserDevice();
            $userDevice->setUser($this->getCurrentRespectiveUserFromTemp($device['user_id']));
            $userDevice->setDeviceId($device['device_id']);
            $userDevice->setCreatedAt(new \DateTime($device['device_id_created_at']));
            if ($device['device_id_updated_at'] != null) {
                $userDevice->setUpdatedAt(new \DateTime($device['device_id_updated_at']));
            }
            $this->_em->persist($userDevice);
        }
        $this->_em->flush();
    }

    /**
     * get user list
     *
     * @param UserIdentity|null $invitor
     * @param string|null $parameter
     * @param bool|null $includeRoleKey
     * @param string|null $currentRole
     * @return array
     */
    public function getUserList(?UserIdentity $invitor, ?string $parameter = null, ?bool $includeRoleKey = false, ?string $currentRole = null): array
    {
//        $allowFromOwner = false;
//        $owners = [];
//        if (!is_null($currentRole) && $currentRole == Constants::PROPERTY_ADMIN_ROLE) {
//            $owners = $this->getOwnerInvitorList($invitor);
//            if (!is_null($owners)) {
//                $allowFromOwner = true;
//            }
//        }

        $select = ['ui.publicId AS userPublicId', 'ui.identifier AS userId', 'u.identifier AS user', 'u.firstLogin', 'd.publicId',
            'CONCAT(ui.firstName, \' \', ui.lastName) AS name', 'u.property AS email', 'ui.enabled',
            'CASE WHEN f.identifier IS NOT NULL THEN true ELSE false END AS isFavourite',
            'CASE WHEN u.firstLogin IS NOT NULL THEN true ELSE false END AS isRegisteredUser', 'ui.isSystemGeneratedEmail',
            'ui.companyName', 'ui.firstName', 'ui.lastName', 'CONCAT(d.firstName, \' \', d.lastName) AS nameDir'];
        if ($includeRoleKey) {
            $select [] = 'r.roleKey';
        }
        $params = ['invitor' => $invitor, 'deleted' => false, 'enabled' => true, 'joinUser' => $invitor];
        $qb = $this->createQueryBuilder('ui')
            ->select($select)
            ->join(User::class, 'u', 'WITH', 'ui.user = u.identifier')
            ->leftJoin(Directory::class, 'd', 'WITH', 'd.user = ui.identifier')
            ->leftJoin('ui.addresses', 'a');
        if ($includeRoleKey) {
            $qb->leftJoin('ui.role', 'r');
        }
        $qb->leftJoin(FavouriteIndividual::class, 'f', 'WITH', 'f.favouriteIndividual = ui.identifier AND f.user = :joinUser')
            ->andWhere('d.invitor = :invitor AND ui.enabled = :enabled AND ui.deleted = :deleted AND d.deleted = :deleted');
//        if ($allowFromOwner) {
//            $params += ['owners' => $owners];
//            $qb->orWhere('d.invitor IN (:owners) AND ui.enabled = :enabled AND ui.deleted = :deleted AND d.deleted = :deleted');
//        }
        if (!empty(trim($parameter))) {
            $qb->andWhere("ui.firstName LIKE :search OR ui.lastName LIKE :search OR u.property LIKE :search OR CONCAT(ui.firstName, ' ', ui.lastName) like :search OR ui.companyName like :search OR
            a.street LIKE :search OR a.streetNumber LIKE :search OR a.city LIKE :search OR a.state LIKE :search OR a.country LIKE :search OR a.zipCode LIKE :search OR a.phone LIKE :search OR a.landLine LIKE :search");
            $params += ['search' => '%' . $parameter . '%'];
        }
        $qb->setParameters($params);

        return $qb->distinct()->getQuery()->getResult();
    }

    /**
     * @param UserIdentity $invitor
     * @return string|null
     */
    public function getOwnerInvitorList(UserIdentity $invitor): ?string
    {
        $owners = [];
        $properties = $this->_em->getRepository(Property::class)->findBy(['administrator' => $invitor, 'deleted' => false]);
        foreach ($properties as $property) {
            $owners[] = $property->getUser()->getIdentifier();
        }

        return count(array_unique($owners)) > 0 ? implode(',', array_unique($owners)) : null;
    }


    /**
     * get company list. future code scope for admin list if possible
     *
     * @param UserIdentity|null $user
     * @param string|null $parameter
     * @return array
     */
    public function getCompanies(?UserIdentity $user = null, ?string $parameter = null): array
    {
        $select = ['u.identifier AS user', 'ui.publicId as publicId', 'ui.publicId as userPublicId',
            'ui.companyName AS name', 'CASE WHEN u.firstLogin IS NOT NULL THEN true ELSE false END AS isRegisteredUser',
            'u.property AS email', 'r.roleKey', 'ui.enabled', 'a.street', 'a.streetNumber', 'a.city', 'a.state', 'a.country',
            'a.zipCode', 'a.latitude', 'a.longitude', 'ui.enabled', 'CASE WHEN f.identifier IS NOT NULL THEN true ELSE false END AS isFavourite',
            'ui.invitedAt', 'ui.isSystemGeneratedEmail', 'ui.companyName', 'ui.firstName', 'ui.lastName'];
        $params = ['deleted' => false, 'enabled' => true, 'role' => 'company', 'user' => $user];
        $qb = $this->createQueryBuilder('ui')
            ->select($select)
            ->join(User::class, 'u', 'WITH', 'ui.user = u.identifier')
            ->join('ui.addresses', 'a')
            ->join('ui.role', 'r')
            ->leftJoin(FavouriteCompany::class, 'f', 'WITH', 'f.favouriteCompany = ui.identifier and f.user = :user')
            ->andWhere('r.roleKey = :role AND ui.enabled = :enabled AND ui.deleted = :deleted AND ui.isAppUseEnabled = :enabled');
        if (!empty(trim($parameter))) {
            $qb->andWhere("ui.firstName LIKE :search OR ui.lastName LIKE :search OR u.property LIKE :search OR CONCAT(ui.firstName, ' ', ui.lastName) like :search OR ui.companyName like :search OR
            a.street LIKE :search OR a.streetNumber LIKE :search OR a.city LIKE :search OR a.state LIKE :search OR a.country LIKE :search OR a.zipCode LIKE :search OR a.phone LIKE :search OR a.landLine LIKE :search");
            $params += ['search' => '%' . $parameter . '%'];
        }
        $qb->setParameters($params);

        return $qb->distinct()->getQuery()->getResult();
    }

    /**
     * get company list based on filter
     *
     * @param array|null $param
     * @return array
     */
    public function getCompaniesBasedOnFilter(?array $param = null): array
    {
        $data = [];
        $damage = '';
        $searchParam = $param['searchKey'];
        $params = ['deleted' => false, 'role' => 'company', 'expired' => false];
        if (isset($param['damage']) && !empty($param['damage'])) {
            $damage = $this->_em->getRepository(Damage::class)->findOneBy(['publicId' => $param['damage']]);
        }
        $companyRejectStatus = $this->_em->getRepository(DamageStatus::class)->findOneBy(['key' => Constants::COMPANY_REJECT_THE_DAMAGE]);
        $query = $this->createQueryBuilder('ui')
            ->select('ui.publicId, ui.firstName, ui.lastName, ui.companyName, u.property as email, ui.identifier AS company,
            ui.enabled as enabledCompany, a.street, a.streetNumber, a.city, a.state, a.country, a.zipCode, a.latitude, a.longitude, ui.isGuestUser')
            ->join(User::class, 'u', 'WITH', 'ui.user = u.identifier')
            ->join('ui.role', 'userRole')
            ->join('ui.addresses', 'a')
            ->where('userRole.roleKey = :role AND ui.deleted = :deleted AND ui.isExpired = :expired');
        if (!empty(trim($searchParam))) {
            $query->andWhere("ui.firstName LIKE :search OR ui.lastName LIKE :search OR
             u.property LIKE :search OR CONCAT(ui.firstName, ' ', ui.lastName) like :search OR ui.companyName like :search");
            $params += ['search' => '%' . $searchParam . '%'];
        }
        if ($damage instanceof Damage) {
            $query->leftJoin(DamageRequest::class, 'r', Expr\Join::WITH, 'ui.identifier = r.company AND r.damage = :damage AND r.deleted = :deleted')
                ->addSelect('CASE WHEN r.company = ui.identifier THEN true ELSE false END AS isAlreadyAssigned');
            $params += ['damage' => $damage->getIdentifier()];
        }
        $query->setParameters($params);
        $results = $query->distinct()->getQuery()->getResult();

        $requestStack = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();
        foreach ($results as $result) {
            $categoryArray = [];
            $result['isAlreadyAssigned'] = isset($result['isAlreadyAssigned']) && $result['isAlreadyAssigned'] === '1';
            $document = $this->_em->getRepository(Document::class)->findOneBy(['user' => $result['company'], 'property' => null,
                'apartment' => null, 'type' => 'coverImage', 'isActive' => true]);
            if ($document instanceof Document) {
                $result['document'] = $this->dmsService->getThumbnails($document->getOriginalName(), $requestStack . '/' . $document->getPath());
            }
            if ($damage instanceof Damage) {
                $allRequest = $this->_em->getRepository(DamageRequest::class)->findBy(['company' => $result['company'], 'deleted' => 0, 'damage' => $damage]);
                $rejectedStatus = 0;
                foreach ($allRequest as $eachRequest) {
                    $eachRequest->getStatus() == $companyRejectStatus ? $rejectedStatus++ : '';
                }
                if ($result['isAlreadyAssigned'] == true && count($allRequest) == $rejectedStatus) {
                    $result['isAlreadyAssigned'] = false;
                }
            }
            $categoriesList = $this->getCompanyCategories(['active' => true, 'deleted' => false, 'company' => $result['company']]);
            foreach ($categoriesList as $eachCategory) {
                $eachCategory['icon'] = $param['iconPath'] . $eachCategory['icon'];
                $categoryArray[] = $eachCategory;
                unset($eachCategory['publicId'], $eachCategory['icon'], $eachCategory['name']);
            }
            $result['category'] = $categoryArray;
            unset($result['company']);
            $data[] = $result;
        }

        return $data;
    }

    /**
     * @param array $parameter
     * @param string|null $locale
     * @return array
     */
    public function getCompanyCategories(array $parameter, string $locale = null): array
    {
        $name = ($locale === 'de') ? ucfirst($locale) : '';
        $query = $this->createQueryBuilder('ui')
            ->select('c.publicId', 'c.icon', 'c.name' . $name)
            ->join('ui.categories', 'c')
            ->where('ui.identifier = :company AND c.deleted = :deleted AND c.active = :active')
            ->setParameters($parameter);

        return $query->distinct()->getQuery()->getArrayResult();
    }

    /**
     * @param UserIdentity|null $user
     * @param string|null $parameter
     * @return array
     */
    public function getJanitors(?UserIdentity $user, ?string $parameter = null): array
    {
        $companyList = $userList = [];
        $companies = $this->getCompanies($user, $parameter);
        $directories = $this->getUserList($user, $parameter, true);
        foreach ($companies as $key => $company) {
            $companyList[] = ['publicId' => $company['userPublicId'], 'name' => $company['name'], 'roleKey' => $company['roleKey'],
                'email' => $company['email'], 'isRegisteredUser' => $company['isRegisteredUser']];
        }
        foreach ($directories as $directory) {
            $userList[] = ['publicId' => $directory['userPublicId'], 'directoryId' => $directory['publicId'], 'name' => $directory['name'],
                'roleKey' => is_null($directory['roleKey']) ? 'individual' : $directory['roleKey'], 'email' => $directory['email'],
                'isRegisteredUser' => !is_null($directory['firstLogin'])];
        }
        $uniqueEmails = [];
        $finalArray = array_merge($companyList, $userList);
        foreach ($finalArray as $key => $array) {
            if (!in_array($array['email'], array_column($uniqueEmails, 'email'))) {
                $uniqueEmails[] = $array;
            }
        }

        return $uniqueEmails;
    }

    /**
     * function to get all property administrators
     *
     * @param UserIdentity|null $user
     * @param string|null $parameter
     * @return array
     */
    public function getAdministrators(?UserIdentity $user = null, ?string $parameter = null): array
    {
        $select = ['ui.publicId as userPublicId', 'ui.companyName AS name',
            'u.property AS email', 'r.roleKey', 'CASE WHEN u.firstLogin IS NOT NULL THEN true ELSE false END AS isRegisteredUser',
            'ui.enabled', 'a.street', 'a.streetNumber', 'a.city', 'a.state', 'a.country', 'a.zipCode',
            'CASE WHEN f.identifier IS NOT NULL THEN true ELSE false END AS isFavourite', 'ui.isSystemGeneratedEmail',
            'ui.firstName', 'ui.lastName'];
        $params = ['deleted' => false, 'enabled' => true, 'roleKey' => 'property_admin', 'user' => $user];
        $qb = $this->createQueryBuilder('ui')
            ->select($select)
            ->join(User::class, 'u', 'WITH', 'ui.user = u.identifier')
            ->leftJoin('ui.addresses', 'a')
            ->join('ui.role', 'r')
            ->leftJoin(FavouriteAdmin::class, 'f', 'WITH', 'f.favouriteAdmin = ui.identifier and f.user = :user')
            ->andWhere('r.roleKey = :roleKey AND ui.enabled = :enabled AND ui.deleted = :deleted');
        if (!empty(trim($parameter))) {
            $qb->andWhere("ui.firstName LIKE :search OR ui.lastName LIKE :search OR u.property LIKE :search OR CONCAT(ui.firstName, ' ', ui.lastName) like :search OR ui.companyName like :search OR
            a.street LIKE :search OR a.streetNumber LIKE :search OR a.city LIKE :search OR a.state LIKE :search OR a.country LIKE :search OR a.zipCode LIKE :search OR a.phone LIKE :search OR a.landLine LIKE :search");
            $params += ['search' => '%' . $parameter . '%'];
        }
        $qb->setParameters($params);
        return $qb->distinct()->getQuery()->getResult();
    }

    /**
     * @param string $param
     * @param string $iconPath
     * @param UserIdentity|null $user
     * @return array
     */
    public function searchCompanies(string $param, string $iconPath, ?UserIdentity $user = null): array
    {
        $list = $this->getCompanies($user, $param);
        $requestStack = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();
        return array_map(function ($users) use ($iconPath, $requestStack, $user) {
            $categoryArray = [];
            $users['isRegisteredUser'] = (bool)$users['isRegisteredUser'];
            if (is_null($users['name'])) {
                $users['name'] = $users['firstName'] . ' ' . $users['lastName'];
            }
            $categoriesList = $this->getCompanyCategories(['active' => true, 'deleted' => false, 'company' => $users['user']], $user->getLanguage());
            foreach ($categoriesList as $eachCategory) {
                $eachCategory['icon'] = $iconPath . $eachCategory['icon'];
                $categoryArray[] = $eachCategory;
                unset($eachCategory['publicId'], $eachCategory['icon'], $eachCategory['name']);
            }
            $users['categories'] = $categoryArray;
            $document = $this->_em->getRepository(Document::class)->findOneBy(
                [
                    'user' => $users['user'],
                    'property' => null,
                    'apartment' => null,
                    'type' => 'coverImage',
                    'isActive' => true
                ]
            );
            if ($document instanceof Document) {
                $users['document'] = $this->dmsService->getThumbnails(
                    $document->getOriginalName(),
                    $requestStack . '/' . $document->getPath()
                );
            }
            return $users;
        }, $list);
    }

    /**
     * @param UserIdentity|null $user
     * @param string $param
     * @return array
     */
    public function searchAdministrators(string $param, ?UserIdentity $user = null): array
    {
        $list = $this->getAdministrators($user, $param);
        return array_map(function ($users) {
            $users['isRegisteredUser'] = (bool)$users['isRegisteredUser'];
            if (is_null($users['name'])) {
                $users['name'] = $users['firstName'] . ' ' . $users['lastName'];
            }
            return $users;
        }, $list);
    }

    /**
     * @param UserIdentity|null $user
     * @param string $param
     * @return array
     */
    public function searchJanitors(?UserIdentity $user, string $param): array
    {
        $list = $this->getJanitors($user, $param);
        return array_map(function ($users) {
            $users['isRegisteredUser'] = (bool)$users['isRegisteredUser'];
            return $users;
        }, $list);
    }

    /**
     * @param int $administrator
     * @return array
     */
    public function findOwners(int $administrator): array
    {
        $qb = $this->createQueryBuilder('ui')
            ->select('ui.identifier')
            ->where('ui.administrator = :administrator AND ui.deleted = :deleted')
            ->setParameters(['deleted' => false, 'administrator' => $administrator]);
        return array_column($qb->getQuery()->getResult(), 'identifier');
    }

    /**
     * function to check if user have given role. return user if found.
     *
     * @param string $user
     * @param string $role
     * @return UserIdentity|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getUserWithRole(string $user, string $role): ?UserIdentity
    {
        $qb = $this->createQueryBuilder('ui')
            ->leftJoin('ui.role', 'r')
            ->andWhere('ui.deleted = :deleted')
            ->andWhere('ui.enabled = :enabled')
            ->andWhere('r.roleKey = :role')
            ->andWhere('ui.publicId = :user')
            ->setParameter('user', $user, 'uuid')
            ->setParameter('deleted', false)
            ->setParameter('enabled', true)
            ->setParameter('role', $role);
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     *
     * @param Address $address
     * @param type $geoService
     * @return void
     */
    public function setGeoCoordinates(Address $address, $geoService): void
    {
        $addressString = '';
        $addressFieldCount = 0;
        if (!empty($address->getStreet())) {
            $addressString .= $address->getStreet() . ',';
            $addressFieldCount++;
        }
        if (!empty($address->getStreetNumber())) {
            $addressString .= $address->getStreetNumber() . ',';
            $addressFieldCount++;
        }
        if (!empty($address->getCity())) {
            $addressString .= $address->getCity() . ',';
            $addressFieldCount++;
        }
        if (!empty($address->getState())) {
            $addressString .= $address->getState() . ',';
            $addressFieldCount++;
        }
        if (!empty($address->getCountry())) {
            $addressString .= $address->getCountry() . ',';
            $addressFieldCount++;
        }
        if ($addressFieldCount > 2 && !empty($addressString)) {
            $coordinates = $geoService->getCoordinates($addressString);
            if (method_exists($coordinates, 'getLatitude')) {
                $address->setLatitude($coordinates->getLatitude());
            } else {
                $address->setLatitude(null);
            }

            if (method_exists($coordinates, 'getLongitude')) {
                $address->setLongitude($coordinates->getLongitude());
            } else {
                $address->setLongitude(null);
            }
        }
    }

    /**
     * Get damage assignees list who didn't responded to an assigned damage after 24 hours
     *
     * @param array $statusArray
     * @param $maxDays
     *
     * @return array
     */
    public function getUnResponsiveDamageAssignees(array $statusArray, int $maxDays): array
    {
        $curDate = new \DateTime('now');
        $qb = $this->createQueryBuilder('u');
        $parameters = array('statusArray' => $statusArray, 'curDate' => $curDate, 'deleted' => false, 'minDays' => 1, 'maxDays' => $maxDays);
        $query = $qb->addSelect('d.id AS damageId, s.key AS statusKey')
            ->innerJoin('App:Damage', 'd', 'WITH', 'd.assignedCompany = u.id OR d.user = u.id')
            ->innerJoin('App:DamageStatus', 's', 'WITH', 's.id = d.status')
            ->where('s.key IN (:statusArray)')
            ->andWhere('DATEDIFF(:curDate, d.updatedAt) >= :minDays')
            ->andWhere('DATEDIFF(:curDate, d.updatedAt) <= :maxDays')
            ->andWhere('u.deleted = :deleted')
            ->setParameters($parameters);

        return $query->getQuery()->getArrayResult();
    }

    /**
     * get all expired companies
     *
     * @return array
     */
    public function getExpiredCompanies(): array
    {
        $curDate = new \DateTime('now');
        $qb = $this->createQueryBuilder('u');
        $param = array('curDate' => $curDate, 'company' => 'company', 'isExpired' => false);
        $query = $qb->select('u')
            ->innerJoin('u.role', 'r', 'WITH', 'r.roleKey = :company')
            ->where('u.isExpired = :isExpired')
            ->andWhere('DATE_DIFF(u.planEndDate, :curDate) < 0')
            ->setParameters($param);
        return $query->getQuery()->getResult();
    }

    /**
     * get all expiring companies (5 days before expiry and 1 day before expiry)
     * @param int $expiryDayLimit
     * @param int $expirationLimitFinal
     * @return array
     */
    public function getAllExpiringCompanies(int $expiryDayLimit, int $expirationLimitFinal)
    {
        $curDate = new \DateTime('now');
        $qb = $this->createQueryBuilder('u');
        $param = array('curDate' => $curDate, 'expirationLimit' => $expiryDayLimit, 'expirationLimitFinal' => $expirationLimitFinal, 'company' => 'company', 'false' => false);
        $query = $qb->select('u')
            ->innerJoin('u.role', 'r', 'WITH', 'r.roleKey = :company')
            ->where('u.isExpired = :false')
            ->andWhere('DATE_DIFF(u.planEndDate, :curDate) = :expirationLimit OR DATE_DIFF(u.planEndDate, :curDate) = :expirationLimitFinal')
            ->setParameters($param);

        return $query->getQuery()->getResult();
    }

    /**
     * Get company list which didn't responded after 24 hours
     *
     * @param array $statusArray
     * @param $maxDays
     * @return array
     */
    public function getunResponsiveCompanyList(array $statusArray, int $maxDays): array
    {
        $curDate = new \DateTime('now');
        $qb = $this->createQueryBuilder('u');
        $parameters = array('statusArray' => $statusArray, 'curDate' => $curDate, 'deleted' => false, 'maxDays' => $maxDays);
        $query = $qb->addSelect('d.identifier AS damageId, d.updatedAt as compareTime')
            ->innerJoin(Damage::class, 'd', 'WITH', 'd.assignedCompany = u.identifier')
            ->innerJoin(DamageStatus::class, 's', 'WITH', 's.identifier = d.status')
            ->where('s.key IN (:statusArray)')
            ->andWhere('DATE_DIFF( :curDate, d.updatedAt) >= 1')
            ->andWhere('DATE_DIFF( :curDate, d.updatedAt) <= :maxDays')
            ->andWhere('u.deleted = :deleted')
            ->setParameters($parameters);

        return $query->getQuery()->getArrayResult();
    }

    /**
     * @param int $user
     * @param string $locale
     * @return array
     */
    public function getUserRoles(int $user, string $locale): array
    {
        $name = ($locale === 'de') ? 'r.nameDe AS name' : 'r.name AS name';
        $qb = $this->createQueryBuilder('ui')
            ->select('ui.publicId AS userPublicId, r.roleKey, r.sortOrder,
             up.permissionKey as permission, ui.isExpired, ui.companyUserRestrictedDate AS companyUserStatus', $name, 'u.firstLogin')
            ->leftJoin('ui.user', 'u')
            ->leftJoin('ui.role', 'r')
            ->leftJoin('ui.userPermission', 'up')
            ->andWhere('ui.identifier = :user')
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }

    /**
     * getActiveCompanyUsers
     *
     * @param UserIdentity $user
     * @param array|null $params
     * @param bool|null $countOnly
     * @param false $subscriptionCheck
     * @return bool|float|int|mixed|string|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getActiveCompanyUsers(UserIdentity $user, ?array $params = [], ?bool $countOnly = false, $subscriptionCheck = false)
    {
        $param = ['parent' => $user, 'deleted' => false];
        $toFetch = ['u.publicId, us.property as email, u.firstName, u.lastName, u.createdAt, u.companyName, u.isExpired, a.streetNumber, 
                    a.street, a.city, a.state, a.country, a.countryCode, a.zipCode, a.phone, u.jobTitle, u.dob',
            'CASE WHEN us.firstLogin IS NOT NULL THEN true ELSE false END AS isRegisteredUser', 'CASE WHEN u.companyUserRestrictedDate IS NOT NULL THEN false ELSE true END AS companyUserStatus', 'u.isSystemGeneratedEmail', 'cs.minPerson', 'cs.maxPerson'];

        $qb = $this->createQueryBuilder('u');
        $qb->select($toFetch)
            ->join('u.parent', 'p')
            ->leftJoin(Address::class, 'a', 'WITH', 'a.user = u.identifier')
            ->leftJoin(User::class, 'us', 'WITH', 'us.identifier = u.user')
            ->leftJoin('u.userPermission', 'up')
            ->leftJoin(Permission::class, 'per', 'WITH', 'per.identifier = up')
            ->leftJoin('u.companySubscriptionPlan', 'cs')
            ->where('u.parent = :parent')
            ->andWhere('u.deleted = :deleted');
        if ($subscriptionCheck == true) {
            $qb->andWhere('u.isExpired = :deleted');
        }
        $qb->orderBy('u.identifier', 'DESC')
            ->setParameters($param);

        if ($countOnly) {
            $qb->select('count(distinct u.identifier) as count');
            return $qb->getQuery()->getSingleScalarResult();
        }

        if (!empty($params['limit'])) {
            $qb->setMaxResults($params['limit']);
        }
        if (!empty($params['offset'])) {
            $qb->setFirstResult($params['offset']);
        }


        $qb->addSelect("GROUP_CONCAT(DISTINCT CONCAT(per.permissionKey, ':', per.name) SEPARATOR '##') AS userPermissions")
            // ->groupBy('u.identifier');
            ->groupBy('u.publicId, us.property, u.firstName, u.lastName, u.createdAt, u.companyName, u.isExpired, 
            a.streetNumber, a.street, a.city, a.state, a.country, a.countryCode, a.zipCode, a.phone, u.jobTitle, u.dob, u.identifier');
        return $qb->getQuery()->getResult();
    }

    /**
     * Check if company is expiring
     *
     * @param UserIdentity $user
     * @param int $expiryDayLimit
     *
     * @return boolean
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function checkIfCompanyIsExpiring(UserIdentity $user, int $expiryDayLimit): bool
    {
        $curDate = new \DateTime('now');
        $qb = $this->createQueryBuilder('u');
        $param = array('user' => $user, 'curDate' => $curDate, 'expiryDayLimit' => $expiryDayLimit, 'recurring' => false);
        $query = $qb->select('COUNT(DISTINCT u.identifier) AS propertyCount')
            ->where('u.identifier = :user')
            ->andWhere('DATE_DIFF(u.planEndDate, :curDate) <= :expiryDayLimit')
            ->andWhere('u.isRecurring = :recurring')
            ->setParameters($param);

        if ($query->getQuery()->getSingleScalarResult() > 0) {
            return true;
        }

        return false;
    }

    /**
     * getCompanyUserDetailss
     *
     * @param array $params
     *
     * @return array
     */
    public function getCompanyUserDetails(array $params): array
    {
        $param = array('user' => $params['companyUser']);
        $toFetch = ['u.publicId, u.identifier, us.property as email, u.firstName, u.lastName, u.createdAt, u.isExpired, a.streetNumber, 
                    a.street, a.city, a.state, a.country, a.countryCode, a.zipCode, a.phone, u.jobTitle, u.dob, us.firstLogin, us.lastLogin, u.invitedAt as invitedOn, u.isSystemGeneratedEmail, ui.expiryDate, ui.isExpired as isCompanyExpired',
            'CASE WHEN us.firstLogin IS NOT NULL THEN true ELSE false END AS isRegisteredUser', 'a.latitude', 'a.longitude', 'u.companyName'];

        $query = $this->createQueryBuilder('u');
        $query->select($toFetch)
            ->addSelect("GROUP_CONCAT(DISTINCT CONCAT(p.permissionKey, ':', p.name) SEPARATOR '##') AS userPermissions")
            ->leftJoin(Address::class, 'a', 'WITH', 'a.user = u.identifier')
            ->leftJoin('u.userPermission', 'up')
            ->leftJoin(Permission::class, 'p', 'WITH', 'p.identifier = up')
            ->leftJoin(User::class, 'us', 'WITH', 'us.identifier = u.user')
            ->join(UserIdentity::class, 'ui', 'WITH', 'ui.identifier = u.parent')
            ->where('u.identifier = :user')
            ->setParameters($param)
            ->groupBy('u.identifier', 'a.identifier');
        return $this->getQueryResultsFormatted($query->getQuery()->getResult());
    }

    /**
     * getQueryResultsFormatted
     *
     * @param array $results
     * @return array
     */
    private function getQueryResultsFormatted(array $results): array
    {
        $res = array();
        if (is_array($results) && !empty($results)) {
            $results = reset($results);
            foreach ($results as $key => $result) {
                $res[$key] = $result;
                $res['isRegisteredUser'] = (bool)$results["isRegisteredUser"];
                if ($key == 'userPermissions' && $result !== '') {
                    $userPermissions = explode('##', $result);
                    $curPerm = [];
                    foreach ($userPermissions as $key2 => $permission) {
                        $curUserPerm = explode(':', $permission);
                        $curPerm[$key2]['key'] = $curUserPerm[0];
                        $curPerm[$key2]['value'] = $curUserPerm[1];
                    }
                    $res[$key] = $curPerm;
                }
            }
        }
        return $res;
    }

    /**
     * get all users
     *
     * @param UserIdentity $user
     * @param array|null $params
     * @param boolean $countOnly (set true for getting count)
     * @param string $locale
     * @return array/int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getAllUsers(UserIdentity $user, ?array $params = [], bool $countOnly = false, $locale = 'en')
    {
        $select = 'u.identifier, u.createdAt, u.firstName, u.lastName, u.enabled, u.expiryDate, u.planEndDate, u.companyName, u.publicId, us.property as email';
        if ($countOnly) {
            $select = 'count(distinct u.identifier) as count';
        }
        $qb = $this->createQueryBuilder('u');
        $query = $qb->select($select)
            ->leftJoin('u.role', 'r', 'WITH')
            ->leftJoin('u.user', 'us')
            ->where('u.deleted = :deleted')
            ->andWhere('u.identifier != :user')
            ->setParameters(['deleted' => 0, 'user' => $user->getIdentifier()]);

        if (!empty($params['search'])) {
            $qb->andWhere("u.firstName LIKE :search OR u.lastName LIKE :search OR us.property LIKE :search OR CONCAT(u.firstName, ' ', u.lastName) like :search OR u.companyName like :search")
                ->setParameter('search', $params['search'] . '%');
        }
        if (!empty($params['limit'])) {
            $qb->setMaxResults($params['limit']);
        }
        if (!empty(($params['offset']))) {
            $qb->setFirstResult($params['offset']);
        }
        if (!empty($params['filter']['enabled'])) {
            $qb->andWhere('u.enabled = :enabled')
                ->setParameter('enabled', $params['filter']['enabled']);
        }
        if (!empty($params['filter']['role'])) {
            $qb->andWhere('r.roleKey = :role')
                ->setParameter('role', $params['filter']['role']);
        }
        if (!$countOnly) {
            $name = ($locale == 'de') ? 'nameDe' : 'name';
            $qb->addSelect("GROUP_CONCAT(DISTINCT CONCAT(r.$name, '', '') SEPARATOR ', ') AS role")
                ->orderBy('u.createdAt', 'DESC')->groupBy('u.identifier');
        } else {
            return $query->getQuery()->getSingleScalarResult();
        }

        return $query->getQuery()->getArrayResult();
    }

    /**
     *
     * @param UserIdentity $user
     * @return int|mixed|string
     */
    public function setCompanyUserStatus(UserIdentity $user)
    {
        $qb = $this->createQueryBuilder('u');
        $query = $qb->update('App\Entity\UserIdentity', 'u')
            ->set('u.companyUserRestrictedDate', ':date')
            ->set('u.isExpired', ':status')
            ->set('u.expiryDate', ':date')
            ->where('u.parent = :user')
            ->setParameters(array('date' => new \DateTime(), 'status' => true, 'user' => $user))
            ->getQuery();

        return $query->execute();
    }

    /**
     * findOneByEmail
     *
     * @param string $email
     * @return UserIdentity|null
     * @throws
     */
    public function findOneByEmail(string $email): ?UserIdentity
    {
        $qb = $this->createQueryBuilder('ui')
            ->join('ui.user', 'u')
            ->where('u.property = :email AND ui.deleted = :deleted')
            ->setParameters(['email' => $email, 'deleted' => false]);

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * findOneByAuthCode
     *
     * @param array $params
     * @return UserIdentity|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByAuthCode(array $params): ?UserIdentity
    {
        $params['deleted'] = false;
        $qb = $this->createQueryBuilder('ui')
            ->join('ui.user', 'u')
            ->where('ui.authCode = :authCode AND ui.deleted = :deleted AND u.property = :email')
            ->setParameters($params);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
