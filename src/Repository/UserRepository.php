<?php

namespace App\Repository;

use App\Entity\CompanySubscriptionPlan;
use App\Entity\Role;
use App\Entity\Temp;
use App\Entity\User;
use App\Entity\UserDevice;
use App\Entity\UserIdentity;
use App\Entity\UserPropertyPool;
use App\Entity\UserSubscription;
use App\Utils\Constants;
use Container70uhdta\getUpdateClientCommandService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use App\Entity\Damage;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    /**
     * UserRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     * @param PasswordAuthenticatedUserInterface $user
     * @param string $newHashedPassword
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Used to load user by id
     * @param string $param
     * @return User|null
     * @throws NonUniqueResultException
     */
    public function loadUserByIdentifier(string $param): ?User
    {
        $em = $this->getEntityManager();

        return $em->createQuery(
            'SELECT u
                    FROM App\Entity\User u
                    WHERE u.username = :query
                    OR u.email = :query'
        )
            ->setParameter('query', $param)
            ->getOneOrNullResult();
    }

    /**
     * @return int|null
     */
    public function getUserCount(): ?int
    {
        try {
            $qb = $this->_em->createQueryBuilder();
            return $qb->select('count(u.property)')
                ->from(User::class, 'u')
                ->getQuery()->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            return 0;
        }
    }

    /**
     * insertUserData
     *
     * @param array $users
     * @throws \Exception
     */
    public function insertUserData(array $users): void
    {
        foreach ($users as $key => $value) {
            if ($this->_em->getRepository(Temp::class)->findOneBy(['oldUserId' => $value['id']]) instanceof Temp) {
                continue;
            }
            $user = $this->loadUserTableData($value);
            $this->loadTempData($user, $value['identifier']);
            $userIdentity = $this->loadUserIdentityData($user, $value);
            $this->loadUserSubscriptionData($userIdentity, $value);
        }
    }

    /**
     * @param array $value
     * @return User
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function loadUserTableData(array $value): User
    {
        $user = new User();
        $user->setEmail($value['username']);
        $user->setPassword($value['password']);
        $user->setConfirmationToken($value['confirmation_token']);
        if (isset($value['password_requested_at']) && $value['password_requested_at'] != '') {
            $user->setPasswordRequestedAt(new \DateTime($value['password_requested_at']));
            $user->setUpdatedAt(new \DateTime($value['updated_at']));
        }
        $user->setDeleted($value['deleted']);
        $user->setRoles(array($value['roles']));
        $user->setCreatedAt(new \DateTime($value['created_at']));
        $user->setIsAcceptedTermsAndConditions($value['changePassword']);
        $user->setConfirmationToken($value['confirmation_token']);
        $user->setIsFirstLogin($value['is_first_login']);
        $user->setFirstName($value['first_name']);
        $user->setLastName($value['last_name']);
        $user->setEnabled($value['enabled']);
        $user->setIsKycVerified($value['is_kyc_verified']);
        $user->setIsBlocked($value['is_blocked']);
        $user->setIsTokenVerified($value['is_token_verified']);
        $user->setIsSocialLogin($value['is_social_login']);
        $user->setIsExistingUser($value['is_existing_user']);
        $user->setIsEmailAvailable($value['is_email_available']);
        $user->setSocialMediaType($value['social_media_type']);
        $user->setSocialMediaUuid($value['social_media_uuid']);
        $user->setMobileNumber($value['mobile_number']);
        $user->setTempMobileNumber($value['temp_mobile_number']);
        $this->_em->persist($user);
        $this->_em->flush();
        return $user;
    }

    /**
     * @param User $user
     * @param int $id
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function loadTempData(User $user, int $id): void
    {
        $temp = new Temp();
        $temp->setUser($user);
        $temp->setOldUserId($id);
        $this->_em->persist($temp);
        $this->_em->flush();
    }

    /**
     * @param User $user
     * @param array $value
     * @return UserIdentity|null
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function loadUserIdentityData(User $user, array $value): ?UserIdentity
    {
        $userIdentity = new UserIdentity();
        $userIdentity->setUser($user);
        $userIdentity->setFirstName($value['first_name']);
        $userIdentity->setLastName($value['last_name']);
        $userIdentity->setLanguage($value['language']);
        $userIdentity->setCreatedAt(new \DateTime($value['created_at']));
        if (isset($value['updated_at']) && $value['updated_at'] != '') {
            $userIdentity->setUpdatedAt(new \DateTime($value['updated_at']));
        }
        $userIdentity->setDeleted($value['deleted']);
        $userIdentity->setLanguage($value['language']);
        $userIdentity->setJobTitle($value['job_title']);
        $userIdentity->setEnabled($value['enabled']);
        $userIdentity->setWebsite($value['website']);
        $userIdentity->setCompanyName($value['company_name']);
        $userIdentity->setIsBlocked($value['is_admin_blocked']);
        if (isset($value['dob']) && $value['dob'] != '') {
            $userIdentity->setDob(new \DateTime($value['dob']));
        }
        $this->_em->persist($userIdentity);
        $this->_em->flush();

        return $userIdentity;
    }

    /**
     * @param UserIdentity $user
     * @param array $value
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function loadUserSubscriptionData(UserIdentity $user, array $value): void
    {
        $userSubscription = new UserSubscription();
        $userSubscription->setUser($user);
        $userSubscription->setStripeSubscription($value['stripe_subscription']);
        $userSubscription->setIsRecurring($value['recurring']);
        $userSubscription->setIsFreePlanSubscribed($value['is_free_plan_subscribed']);
        $userSubscription->setIsExpired($value['is_expired']);
        if (isset($value['expiry_date']) && $value['expiry_date'] != '') {
            $userSubscription->setPlanEndDate(new \DateTime($value['expiry_date']));
        }
        $companySubscriptionPlan = $this->_em->getRepository(CompanySubscriptionPlan::class)
            ->findOneBy(['identifier' => $value['company_subscription_plan']]);
        $userSubscription->setCompanySubscriptionPlan($companySubscriptionPlan);
        $this->_em->persist($userSubscription);
        $this->_em->flush();
    }

    /**
     * @param User $user
     * @return UserIdentity|null
     */
    public function getUserIdentityFromUser(User $user): ?UserIdentity
    {
        return $this->_em->getRepository(UserIdentity::class)->findOneBy(['user' => $user]);
    }

    /**
     * Check if valid admin
     *
     * @param integer $userId
     * @param integer $adminUserId
     * @return bool
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function userValidAdmin(int $userId, int $adminUserId): bool
    {
        $qb = $this->createQueryBuilder('u');
        $param = array('userId' => $userId, 'adminUserId' => $adminUserId);
        $query = $qb->select('COUNT(DISTINCT u.identifier) AS userCount')
            ->where('u.identifier = :userId')
            ->andWhere('u.administrator = :adminUserId')
            ->setParameters($param);

        if ($query->getQuery()->getSingleScalarResult() > 0) {
            return true;
        }
        return false;
    }

    /**
     * @param array $params
     * @param string $locale
     * @return array
     */
    public function getUserRoles(array $params, string $locale): array
    {
        $name = ($locale === 'de') ? 'ur.nameDe AS name' : 'ur.name AS name';
        $qb = $this->createQueryBuilder('u')
            ->select('ui.publicId AS userPublicId, ur.roleKey', 'ur.sortOrder', 'up.permissionKey as permission', 'ui.isExpired', 'ui.companyUserRestrictedDate AS companyUserStatus', $name)
            ->join(UserIdentity::class, 'ui', 'WITH', 'ui.user = u.identifier')
            ->leftJoin('ui.role', 'ur')
            ->leftJoin('ui.userPermission', 'up')
            ->where('u.property = :property AND u.deleted = :deleted AND ui.deleted = :deleted')
            ->orderBy('ur.sortOrder', 'ASC')
            ->setParameters($params);
        return $qb->getQuery()->getResult();
    }

    /**
     * Get the list of companies who haven't yet responded after 24 hours
     *
     * @param array $statusArray
     * @param $maxDays
     *
     * @return array
     */
    public function getunResponsiveCompanyList(array $statusArray, int $maxDays): array
    {
        $curDate = new \DateTime('now');
        $qb = $this->createQueryBuilder('u');
        $parameters = array('statusArray' => $statusArray, 'curDate' => $curDate, 'deleted' => false, 'maxDays' => $maxDays);
        $query = $qb->addSelect('d.identifier AS damageId, d.updatedAt as compareTime')
            ->innerJoin(Damage::class, 'd', 'WITH', 'd.assignedCompany = u.identifier')
            ->innerJoin('d.status', 's')
            ->where('s.key IN (:statusArray)')
            ->andWhere('DATE_DIFF( :curDate, d.updatedAt) >= 1')
            ->andWhere('DATE_DIFF( :curDate, d.updatedAt) <= :maxDays')
            ->andWhere('u.deleted = :deleted')
            ->setParameters($parameters);

        return $query->getQuery()->getArrayResult();
    }

    /**
     * Get the list of damage assignees who haven't yet responded to assigned damage after 24 hours.
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
        $query = $qb->addSelect('d.identifier AS damageId, s.key AS statusKey')
            ->innerJoin(Damage::class, 'd', 'WITH', 'd.assignedCompany = u.identifier OR d.user = u.identifier')
            ->innerJoin('d.status', 's')
            ->where('s.key IN (:statusArray)')
            ->andWhere('DATE_DIFF(:curDate, d.updatedAt) >= :minDays')
            ->andWhere('DATE_DIFF(:curDate, d.updatedAt) <= :maxDays')
            ->andWhere('u.deleted = :deleted')
            ->setParameters($parameters);

        return $query->getQuery()->getArrayResult();
    }

    /**
     * Get guest user role for guest login
     *
     * @param array $params
     * @return array
     * @throws NonUniqueResultException
     */
    public function getGuestUserRole(array $params): array
    {
        $userPublicId = $this->createQueryBuilder('u')
            ->select('ui.publicId')
            ->join(UserIdentity::class, 'ui', 'WITH', 'ui.user = u.identifier')
            ->where('u.property = :property AND u.deleted = :deleted')
            ->setParameters($params)
            ->getQuery()->getOneOrNullResult();
        $result['userPublicId'] = $userPublicId['publicId'];
        $result['role'] = Constants::COMPANY_ROLE;
        $result['roleKey'] = Constants::COMPANY_ROLE;
        return $result;
    }

}
