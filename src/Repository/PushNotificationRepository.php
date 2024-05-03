<?php

namespace App\Repository;

use App\Entity\PushNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\UserIdentity;
use App\Repository\Traits\RepositoryTrait;
use Doctrine\ORM\QueryBuilder;

/**
 * @method PushNotification|null find($id, $lockMode = null, $lockVersion = null)
 * @method PushNotification|null findOneBy(array $criteria, array $orderBy = null)
 * @method PushNotification[]    findAll()
 * @method PushNotification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PushNotificationRepository extends ServiceEntityRepository
{
    use RepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PushNotification::class);
    }

    /**
     * Method to return user notifications
     *
     * @param UserIdentity $user
     * @param string $currentRole
     * @param int $count
     * @param int $startPage
     * @param array filter
     * @return array
     */
    public function getUserNotifications(UserIdentity $user, string $currentRole, int $count = null, int $startPage = null, ?array $filter = []): array
    {
        $qb = $this->createQueryBuilder('n')
            ->select('n.identifier, n.message, n.messageDe, n.readMessage as isRead, n.createdAt, n.event, n.publicId as notificationId, d.publicId as damageId,  r.roleKey');
        $this->applyBaseConditions($qb, $user, $currentRole, $filter);
        $qb->orderBy('n.createdAt', 'DESC');

        return $this->handlePagination($qb, $startPage, $count);
    }

    /**
     * Method to return user's read notification count
     *
     * @param UserIdentity $user
     * @param string $currentRole
     *
     * @return int
     */
    public function getTotalReadCount(UserIdentity $user, string $currentRole): int
    {
        $qb = $this->createQueryBuilder('n')
            ->select('count(n.identifier)');
        $this->applyBaseConditions($qb, $user, $currentRole, ['isRead' => 1]);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Method to return user's total notification count
     *
     * @param UserIdentity $user
     * @param string $currentRole
     *
     * @return int
     */
    public function getTotalRowCount(UserIdentity $user, string $currentRole): int
    {
        $qb = $this->createQueryBuilder('n')
            ->select('count(n.identifier)');
        $this->applyBaseConditions($qb, $user, $currentRole);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get notification base query
     *
     * @param QueryBuilder $qb
     * @param UserIdentity $user
     * @param string $currentRole
     * @param array $filter
     *
     * @return int
     */
    private function applyBaseConditions(QueryBuilder $qb, UserIdentity $user, string $currentRole, ?array $filter = [])
    {
        $qb->leftJoin('n.damage', 'd')
            ->leftJoin('n.role', 'r')
            ->where('n.toUser = :user')
            ->setParameter('user', $user)
            ->andWhere('n.deleted = :isDeleted')
            ->andWhere('r.roleKey = :roleKey')
            ->setParameter('isDeleted', 0)
            ->setParameter('roleKey', $currentRole);

        if (isset($filter['isRead'])) {
            $qb->andWhere('n.readMessage = :isRead')
                ->setParameter('isRead', $filter['isRead']);
        }

        return $qb;
    }

}
