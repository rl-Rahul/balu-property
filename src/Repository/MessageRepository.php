<?php

namespace App\Repository;

use App\Entity\Damage;
use App\Entity\DamageOffer;
use App\Entity\Message;
use App\Utils\Constants;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\UserIdentity;
use App\Repository\Traits\ReadStatusTrait;
use App\Repository\Traits\RepositoryTrait;
use App\Entity\PropertyUser;
use Doctrine\ORM\QueryBuilder;

/**
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    use ReadStatusTrait;
    use RepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * getAllDamages
     *
     * Function to get all messages
     *
     * @param UserIdentity $user
     * @param string $currentRole
     * @param array|null $params
     * @param bool $countOnly
     * @param bool|null $lightApi
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getAllMessages(UserIdentity $user, string $currentRole, array $params = null, bool $countOnly = false, ?bool $lightApi = false): array
    {
        $qb = $this->createQueryBuilder('d');
        if ($lightApi === false) {
            $query = $qb->addSelect('d');
        } else {
            $query = $qb->addSelect('d.publicId');
        }
        $qb->orderBy('d.updatedAt', 'DESC');
        $qb->leftJoin('d.createdBy', 'u');
        $qb->leftJoin('d.apartments', 'a');
        $qb->leftJoin('a.property', 'p');
        $qb->leftJoin('d.damage', 'dd');
        $qb->leftJoin('dd.apartment', 'da');
        $qb->leftJoin('da.property', 'ddp');
        if ($currentRole === Constants::PROPERTY_ADMIN_ROLE) {
            $qb->leftJoin('p.administrator', 'ad');
            $qb->leftJoin('ddp.administrator', 'ddpad');
            $qb->andWhere('(p.administrator = :administrator OR ad.administrator = :administrator) '
                . 'OR  (ddp.administrator = :administrator OR ddpad.administrator = :administrator)')
                ->setParameter('administrator', $user);
        } elseif ($currentRole === Constants::OWNER_ROLE) {
            $qb->leftJoin('p.user', 'ad');
            $qb->leftJoin('ddp.user', 'dad');
            $qb->andWhere('(p.user = :owner OR ad.administrator = :owner) '
                . 'OR (ddp.user = :owner OR dad.administrator = :owner)')
                ->setParameter('owner', $user);
        } elseif ($currentRole === Constants::JANITOR_ROLE) {
            $qb->leftJoin('p.janitor', 'ad');
            $qb->leftJoin('ddp.janitor', 'ddpad');
            $qb->andWhere('(p.janitor = :janitor OR ad.administrator = :janitor) '
                . 'OR (ddp.janitor = :janitor OR ddpad.administrator = :janitor)')
                ->setParameter('janitor', $user);
        } elseif ($currentRole === Constants::COMPANY_ROLE) {
            $qb->join(DamageOffer::class, 'do', 'WITH', 'do.damage = dd.identifier');
            $qb->andWhere('do.company = :company AND do.acceptedDate IS NOT NULL')
                ->setParameter('company', $user);
        } elseif ($currentRole === Constants::COMPANY_USER_ROLE) {
            $company = $this->_em->getRepository(UserIdentity::class)->findOneBy(['identifier' => $user->getParent()]);
            $qb->join(DamageOffer::class, 'do', 'WITH', 'do.damage = dd.identifier');
            $qb->andWhere('do.company = :company AND do.acceptedDate IS NOT NULL')
                ->setParameter('company', $company);
        } else {
            $qb->leftJoin(PropertyUser::class, 'pu', 'WITH', 'a.identifier = pu.object');
            $qb->leftJoin('pu.role', 'r');
            $qb->leftJoin('pu.user', 'ad');
            $qb->leftJoin(PropertyUser::class, 'dpu', 'WITH', 'da.identifier = dpu.object');
            $qb->leftJoin('dpu.role', 'dpur');
            $qb->leftJoin('dpu.user', 'dpuad');
            $qb->andWhere('((pu.user = :user OR ad.administrator = :user) and r.roleKey = :role and pu.deleted = 0 and pu.isActive=1)'
                . 'OR ((dpu.user = :user OR dpuad.administrator = :user) and dpur.roleKey = :role and dpu.deleted = 0 and dpu.isActive=1)')
                ->setParameter('user', $user)
                ->setParameter('role', $currentRole);
        }
        if (null !== $params['status']) {
            $qb->andWhere('d.archive = :status')
                ->setParameter('status', ($params['status'] == 'archive') ? 1 : 0);
        }
        if (!empty($params['apartment'])) {
            $qb->join('d.apartment', 'a');
            $qb->andWhere('a.publicId = :apartment')
                ->setParameter('apartment', Uuid::fromRfc4122($params['apartment']));
        }
        if (!empty($params['damage'])) {
            $qb->andWhere('d.damage = :damage')
                ->setParameter('damage', $params['damage']);
        }
        if (null !== $params['type']) {
            $qb->andWhere('d.type = :type')
                ->setParameter('type', $params['type']);
        }
        if (!empty($params['text'])) {
            $this->applyFreeTextFilter($params['text'], $qb);
        }
        if (!empty($params['limit'])) {
            $qb->setMaxResults($params['limit']);
        }
        if (!empty($params['offset'])) {
            $qb->setFirstResult($params['offset']);
        }
        $qb->andWhere('d.deleted = :deleted')
            ->setParameter('deleted', false);
        if ($countOnly) {
            $qb->select('count(d)');
            return $query->getQuery()->getSingleScalarResult();
        }

        return $query->getQuery()->getResult();
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
        $idSearch = $this->searchWithId($query, $text, 'dd.identifier');
        if (null !== $idSearch) {
            return $idSearch;
        }

        return $query->andWhere('REGEXP(d.subject,dd.identifier, u.firstName, u.lastName, :pattern) = true')
            ->setParameter('pattern', $this->generateRegex($text));
    }

    /**
     *
     * @param Message $message
     * @return void
     */
    public function setMessageArchived(Message $message): void
    {
        $this->createQueryBuilder('d')
            ->update()
            ->set('d.archive', ':status')
            ->setParameter('status', 1)
            ->where('d.identifier = :message')
            ->setParameter('message', $message->getIdentifier())
            ->getQuery()
            ->execute();
    }

    /**
     *
     * @param array $ids
     * @return void
     */
    public function deleteMessages(array $ids): void
    {
        $qb = $this->createQueryBuilder('m');
        $query = $qb->update('App\Entity\Message', 'm')
            ->set('m.deleted', ':deleted')
            ->where('m.damage in (:ids)')
            ->setParameters(array('deleted' => true, 'ids' => $ids))
            ->getQuery();
        $query->execute();
    }
}
