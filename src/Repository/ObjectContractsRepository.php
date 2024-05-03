<?php

namespace App\Repository;

use App\Entity\Folder;
use App\Entity\ObjectContracts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Apartment;
use App\Entity\RentalTypes;
use App\Entity\NoticePeriod;
use App\Repository\Traits\RepositoryTrait;
use App\Utils\Constants;

/**
 * @method ObjectContracts|null find($id, $lockMode = null, $lockVersion = null)
 * @method ObjectContracts|null findOneBy(array $criteria, array $orderBy = null)
 * @method ObjectContracts[]    findAll()
 * @method ObjectContracts[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ObjectContractsRepository extends ServiceEntityRepository
{
    use RepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ObjectContracts::class);
    }

    /**
     * get folders of a user
     *
     * @param array $params
     * @return array
     */
    public function getFolders(array $params): array
    {
        $params['deleted'] = false;
        $qb = $this->createQueryBuilder('p');
        $query = $qb
            ->select('f.identifier', 'f.name', 'f.publicId AS folderId', 'p.publicId', 'f.displayName')
            ->leftJoin(Folder::class, 'f', 'WITH', 'p.folder = f.identifier')
            ->where('p.createdBy = :user')
            ->andWhere('p.deleted = :deleted')
            ->orderBy('p.createdAt', 'DESC')
            ->setParameters($params);
        return $query->getQuery()->getResult();
    }

    /**
     *
     * @param Apartment $object
     * @param string $locale
     * @param string|null $sortBy
     * @param string|null $sortOrder
     * @param int|null $count
     * @param int|null $startPage
     * @return array
     */
    public function getContractList(Apartment $object, string $locale, ?string $sortBy, ?string $sortOrder = 'ASC', ?int $count = 0, ?int $startPage): array
    {
        $qb = $this->createQueryBuilder('o');
        $qb->select("o.publicId, o.status, o.active, o.startDate, o.endDate, o.ownerVote, o.additionalComment, r.publicId as rentalType, r.name$locale as rentalTypeName, n.publicId as noticePeriod, n.name$locale as noticePeriodName")
            ->leftJoin(NoticePeriod::class, 'n', 'WITH', 'o.noticePeriod = n.identifier')
            ->leftJoin(RentalTypes::class, 'r', 'WITH', 'o.rentalType = r.identifier')
            ->where('o.object = :object')
            ->andWhere('o.deleted = :deleted')
            ->setParameters(['object' => $object, 'deleted' => 0]);
        if (null === $sortBy) {
            $sortBy = 'o.active';
        }
        if (!($sortOrder == 'ASC' || $sortOrder == 'DESC')) {
            $sortOrder = 'ASC';
        }
        $qb->orderBy($sortBy, $sortOrder);

        return $this->handlePagination($qb, $startPage, $count);
    }

    /**
     * get active tenant based on current date
     *
     * @param \DateTime $curDate
     * @return array
     */
    public function getActiveContractByDate(\DateTime $curDate): array
    {
        $qb = $this->createQueryBuilder('o');
        $query = $qb->where('o.endDate < :curDate OR o.endDate IS NULL')
            ->andWhere('o.active = :active')
            ->andWhere('o.deleted = :deleted')
            ->setParameters(['curDate' => $curDate, 'active' => 1, 'deleted' => 0]);

        return $query->getQuery()->getResult();
    }

    /**
     *
     * @param Apartment $aprtment
     * @param \DateTime $curDate
     * @return ObjectContracts
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getFutureContractByApartment(Apartment $aprtment, \DateTime $curDate): ?ObjectContracts
    {
        $qb = $this->createQueryBuilder('t');
        $query = $qb->where('t.startDate <= :curDate')
            ->andWhere('t.endDate > :curDate OR t.endDate IS NULL')
            ->andWhere('t.active = :active')
            ->andWhere('t.deleted = :deleted')
            ->andWhere('t.status = :status')
            ->setMaxResults(1)
            ->setParameters(['curDate' => $curDate, 'active' => 0, 'deleted' => 0, 'status' => Constants::CONTRACT_STATUS_FUTURE])
            ->orderBy('t.startDate', 'ASC');

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     *
     * @param Apartment $apartment
     * @param int|null $currentContract
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getFutureContractCount(Apartment $apartment, ?int $currentContract = null): int
    {
        $params = ['status' => Constants::CONTRACT_STATUS_FUTURE, 'object' => $apartment->getIdentifier(), 'active' => 0, 'deleted' => 0];
        $qb = $this->createQueryBuilder('t');
        $query = $qb->select('count(t.identifier)')
            ->where('t.status = :status')
            ->andWhere('t.deleted = :deleted')
            ->andWhere('t.object = :object')
            ->andWhere('t.active = :active');
        if (!is_null($currentContract)) {
            $qb->andWhere('t.identifier != :currentContract');
            $params += ['currentContract' => $currentContract];
        }
        $qb->setParameters($params)
            ->orderBy('t.startDate', 'ASC');
        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     *
     * @param Apartment $apartment
     * @return array
     */
    public function getAllFutureContracts(Apartment $apartment): array
    {
        $qb = $this->createQueryBuilder('t');
        $query = $qb->select('t.startDate, t.endDate')
            ->where('t.status = :status')
            ->andWhere('t.deleted = :deleted')
            ->andWhere('t.object = :object')
            ->andWhere('t.active = :active')
            ->setParameters(['status' => Constants::CONTRACT_STATUS_FUTURE, 'object' => $apartment, 'active' => 0, 'deleted' => 0])
            ->orderBy('t.startDate', 'ASC');

        return $query->getQuery()->getResult();
    }

    /**
     *
     * @param Apartment $apartment
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getOpenContracts(Apartment $apartment): int
    {
        $qb = $this->createQueryBuilder('t');
        $query = $qb->select('count(t.identifier)')
            ->where('t.status != :archived')
            ->andWhere('t.deleted = :deleted')
            ->andWhere('t.object = :object')
            ->setParameters(['archived' => Constants::CONTRACT_STATUS_ARCHIVED, 'object' => $apartment, 'deleted' => 0])
            ->orderBy('t.startDate', 'ASC');

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     *
     * @param Apartment $apartment
     * @param string|null $contract
     * @return array
     */
    public function getAllValidContracts(Apartment $apartment, ?string $contract = null): array
    {
        $params = ['archived' => Constants::CONTRACT_STATUS_ARCHIVED, 'object' => $apartment, 'deleted' => 0];
        $qb = $this->createQueryBuilder('t');
        $query = $qb->select('t.startDate, t.endDate, r.type, t.status, t.terminationDate, t.identifier')
            ->leftJoin('t.rentalType', 'r', 'WITH', 't.rentalType = r.identifier')
            ->where('t.status != :archived')
            ->andWhere('t.deleted = :deleted')
            ->andWhere('t.object = :object');
        if (!is_null($contract)) {
            $contract = $this->findOneBy(['publicId' => $contract]);
            $qb->andWhere('t.identifier != :contractToSkip');
            $params += ['contractToSkip' => $contract->getIdentifier()];
        }
        $qb->setParameters($params)
            ->orderBy('t.startDate', 'ASC');

        return $query->getQuery()->getResult();
    }

    /**
     * get active tenant based on current date
     * @param \DateTime $curDate
     * @param Apartment|null $apartment
     * @return array|ObjectContracts
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getNextFutureContract(\DateTime $curDate, ?Apartment $apartment = null)
    {
        $qb = $this->createQueryBuilder('o');
        $qb->where('o.startDate <= :curDate')
            ->andWhere('o.active = :active')
            ->andWhere('o.status = :futureStatus')
            ->andWhere('o.deleted = :deleted')
            ->setParameters(['deleted' => 0, 'curDate' => $curDate, 'active' => 0, 'futureStatus' => Constants::CONTRACT_STATUS_FUTURE]);
        if (!is_null($apartment)) {
            $qb->andWhere('o.object = :object')
                ->setParameter('object', $apartment)
                ->orderBy('o.startDate', 'ASC')
                ->setMaxResults(1);
            return $qb->getQuery()->getOneOrNullResult();
        }

        return $qb->getQuery()->getResult();
    }

    /**
     *
     * @param Apartment $apartment
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function checkForActiveContract(Apartment $apartment): int
    {
        $qb = $this->createQueryBuilder('o');
        $query = $qb->select('count(o.identifier)')
            ->where('o.object = :object')
            ->andWhere('o.active = :active')
            ->andWhere('o.deleted = :deleted')
            ->andWhere('o.status = :status')
            ->setParameters(['object' => $apartment, 'active' => 1, 'deleted' => 0, 'status' => Constants::CONTRACT_STATUS_ACTIVE]);

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     *
     * @param Apartment $apartment
     * @param RentalTypes $rentalType
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function checkOpenRentalContract(Apartment $apartment, RentalTypes $rentalType): int
    {
        $qb = $this->createQueryBuilder('o');
        $query = $qb->select('count(o.identifier)')
            ->where('o.object = :object')
            ->andWhere('o.deleted = :deleted')
            ->andWhere('o.rentalType = :rentalType')
            ->setParameters(['object' => $apartment, 'deleted' => 0, 'rentalType' => $rentalType]);

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     *
     * @param Apartment $apartment
     */
    public function deleteContracts(Apartment $apartment)
    {
        $qb = $this->createQueryBuilder('d');
        $query = $qb->update('App\Entity\ObjectContracts', 'd')
            ->set('d.deleted', ':deleted')
//                       ->set('d.status', ':status')
            ->where('d.object = :object')
            ->setParameters(array('deleted' => true, 'object' => $apartment))
            ->getQuery();
        return $query->execute();
    }
}
