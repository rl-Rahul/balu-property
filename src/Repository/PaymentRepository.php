<?php

namespace App\Repository;

use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\UserIdentity;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Entity\CompanySubscriptionPlan;
use App\Entity\SubscriptionPlan;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method Payment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Payment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Payment[]    findAll()
 * @method Payment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentRepository extends ServiceEntityRepository
{
    private ParameterBagInterface $params;

    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     *
     * @param ManagerRegistry $registry
     * @param ParameterBagInterface $params
     */
    public function __construct(ManagerRegistry $registry, ParameterBagInterface $params, TranslatorInterface $translator)
    {
        parent::__construct($registry, Payment::class);
        $this->params = $params;
        $this->translator = $translator;
    }

    /**
     * get list of payments done by currently logged in user
     *
     * @param BpUser $user
     * @param array $params
     * @param string $userRole
     * @param $container
     * @param bool $admin
     *
     * @return array
     */
    public function getListOfPaymentsByLoggedInUser(UserIdentity $user, array $params, string $userRole, bool $admin = false)
    {
        $companyRole = $this->params->get('user_roles')['company_user'];
        $query = $this->createQueryBuilder('p');
        $param = (!$admin) ? array('user' => $user, 'isCompany' => 1) : array('isCompany' => 1);
        if ($userRole !== $companyRole) {
            unset($param['isCompany']);
            $query->select('p.isCompany, p.amount, p.transactionId, IDENTITY(p.user) AS user, r.roleKey, r.name AS userRole, r.identifier AS roleId,
                           p.response, p.createdAt, p.identifier AS payment, pr.identifier AS property, u.firstName, u.lastName,
                           p.isSuccess, pr.address AS name, p.period, pr.recurring, pr.planEndDate, u.companyName AS companyName, 
                           u.isRecurring AS companyRecurring, u.expiryDate AS companyPlanEndDate, c.period AS companyPeriod')
                ->join(UserIdentity::class, 'u', 'WITH', 'p.user = u.identifier')
                ->join('u.role', 'r')
                ->leftJoin('p.property', 'pr')
                ->leftJoin(CompanySubscriptionPlan::class, 'c', 'WITH', 'c.identifier = u.companySubscriptionPlan')
                ->leftJoin(SubscriptionPlan::class, 's', 'WITH', 's.identifier = pr.subscriptionPlan');
            if (!$admin) {
                $query->where('p.user = :user');
            }
        } else {
            $query->select('p.isCompany, p.amount, p.transactionId, u.identifier AS user, p.response, p.createdAt, r.name AS userRole, 
                            r.roleKey, r.identifier AS roleId, p.identifier AS payment, p.isSuccess, p.period, u.companyName AS name, u.recurring,
                            u.firstName, u.lastName, u.expiryDate AS planEndDate')
                ->leftJoin(UserIdentity::class, 'u', 'WITH', 'u.identifier = p.user AND p.isCompany = :isCompany')
                ->join('u.role', 'r')
                ->leftJoin(CompanySubscriptionPlan::class, 'c', 'WITH', 'c.identifier = u.companySubscriptionPlan')
                ->where('p.user = :user');
        }
        $query->setParameters($param)
            ->orderBy('p.identifier', 'DESC');
        if (!empty($params['limit'])) {
            $query->setMaxResults($params['limit']);
        }
        if (!empty($params['offset'])) {
            $query->setFirstResult($params['offset']);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * get count of payments done by currently logged in user
     *
     * @param BpUser $user
     * @param string $userRole
     * @param $container
     * @param bool $admin
     *
     * @return array
     */
    public function getCountOfPaymentsByLoggedInUser(UserIdentity $user, string $userRole, bool $admin = false)
    {
        $companyUserRole = $this->params->get('user_roles')['company_user'];
        $param = (!$admin) ? array('user' => $user, 'isCompany' => 1) : array('isCompany' => 1);
        $query = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.identifier) AS paymentCount');
        if ($userRole !== $companyUserRole) {
            unset($param['isCompany']);
            $query->leftJoin('p.property', 'pr')
                ->leftJoin(SubscriptionPlan::class, 's', 'WITH', 's.identifier = pr.subscriptionPlan');
            if (!$admin) {
                $query->where('p.user = :user');
            }
        } else {
            $query
                ->leftJoin(UserIdentity::class, 'u', 'WITH', 'u.identifier = p.user AND p.isCompany = :isCompany')
                ->leftJoin(SubscriptionPlan::class, 'c', 'WITH', 'c.identifier = u.companySubscriptionPlan')
                ->where('p.user = :user');
        }
        $query->setParameters($param)
            ->orderBy('p.createdAt', 'DESC');


        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * check same transaction id exist for the current date
     *
     * @param string $transactionId
     * @return array
     */
    public function checkPaymentLog(string $transactionId)
    {
        $param[':transactionId'] = $transactionId;
        $em = $this->getEntityManager();
        $myCon = $em->getConnection();
        $query = "SELECT p.id FROM  bp_payment p WHERE p.transaction_id = :transactionId and DATE(p.created_at) = DATE(NOW())";
        $result = $myCon->prepare($query);
        $result->execute($param);

        return $result->fetchAll();
    }


    /**
     * @param array $criterion
     * @param array $params
     * @param bool $countOnly
     *
     * @return array
     */
    public function searchPayment(array $criterion, array $params, bool $countOnly = false)
    {
        $i = 0;
        $param = [];
        $condition = ['email' => 'u.username = :email', 'transactionId' => 'p.transactionId = :transactionId',
            'startDate' => 'p.createdAt >= :startDate', 'endDate' => 'p.createdAt <= :endDate'];

        $toSelect = array('p.isCompany, p.amount, p.transactionId, IDENTITY(p.user) AS user, r.roleKey, r.name AS userRole, r.identifier AS roleId,
                           p.response, p.createdAt, p.identifier AS payment, pr.identifier AS property, u.firstName, u.lastName,
                           p.isSuccess, pr.address AS name, s.period, pr.recurring, pr.planEndDate, u.companyName AS companyName, 
                           u.isRecurring AS companyRecurring, u.expiryDate AS companyPlanEndDate, c.period AS companyPeriod');

        if ($countOnly) {
            $toSelect = 'COUNT(DISTINCT p.id) AS paymentCount';
        }

        $query = $this->createQueryBuilder('p')
            ->select($toSelect)
            ->join('p.user', 'u')
            ->join('u.role', 'r')
            ->leftJoin('p.property', 'pr')
            ->leftJoin('u.companySubscriptionPlan', 'c')
            ->leftJoin('pr.subscriptionPlan', 's');

        foreach ($criterion as $key => $value) {
            if (!is_null($value)) {
                $where = ($i == 0) ? 'where' : 'andWhere';
                $query->$where($condition[$key]);
                $param[$key] = $value;

                $i++;
            }
        }

        $query->setParameters($param);
        if (!empty($params['limit'])) {
            $query->setMaxResults($params['limit']);
        }
        if (!empty($params['offset'])) {
            $query->setFirstResult($params['offset']);
        }

        if ($countOnly) {
            return $query->getQuery()->getSingleScalarResult();
        }

        $query->orderBy('p.identifier', 'DESC');

        return $query->getQuery()->getResult();
    }

    /**
     *
     *  get list of payments done by currently logged in user
     *
     * @param UserIdentity $user
     * @param array $params
     * @param string $userRole
     * @param bool $admin
     * @param bool $countOnly
     * @param string|null $locale
     * @return bool|float|int|mixed|string|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getAllPayments(UserIdentity $user, array $params, string $userRole, bool $admin = false, bool $countOnly = false, ?string $locale = 'en')
    {
        $freePlan = $this->translator->trans('freePlanAdminText', [], null, $locale);
        $planName = ($locale === 'de') ? 's.nameDe AS subscriptionPlan' : 's.name AS subscriptionPlan';
        $query = $this->createQueryBuilder('p');
        $toSelect = ['p.isCompany, p.amount, IDENTITY(p.user) AS user, r.roleKey, r.name AS userRole, r.identifier AS roleId,
                       p.response, p.createdAt, p.identifier AS payment, pr.identifier AS property, u.firstName, u.lastName,
                       p.isSuccess, pr.address AS name, p.period, pr.recurring, pr.planEndDate, u.companyName AS companyName, 
                       u.isRecurring AS companyRecurring, u.expiryDate AS companyPlanEndDate, c.period AS companyPeriod',
            "CASE WHEN p.transactionId IS NULL THEN '$freePlan' ELSE p.transactionId END as transactionId", $planName];
        if ($countOnly) {
            $toSelect = 'COUNT(DISTINCT p.identifier) AS count';
        }
        $query->select($toSelect)
            ->join(UserIdentity::class, 'u', 'WITH', 'p.user = u.identifier')
            ->join('u.role', 'r')
            ->leftJoin('p.property', 'pr')
            ->leftJoin(CompanySubscriptionPlan::class, 'c', 'WITH', 'c.identifier = u.companySubscriptionPlan')
            ->leftJoin(SubscriptionPlan::class, 's', 'WITH', 's.identifier = pr.subscriptionPlan');
        if (isset($params['searchKey']) && !empty(trim($params['searchKey']))) {
            $query->andWhere("p.transactionId LIKE :search OR u.lastName LIKE :search OR CONCAT(u.firstName, ' ', u.lastName) like :search OR u.companyName like :search")
                ->setParameter('search', '%' . $params['searchKey'] . '%');
        }
        if ($countOnly) {
            return $query->getQuery()->getSingleScalarResult();
        }
        $query->orderBy('p.identifier', 'DESC');
        if (!empty($params['limit'])) {
            $query->setMaxResults($params['limit']);
        }
        if (!empty($params['offset'])) {
            $query->setFirstResult($params['offset']);
        }

        return $query->getQuery()->getResult();
    }
}
