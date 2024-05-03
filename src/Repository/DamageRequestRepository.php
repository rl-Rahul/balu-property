<?php

namespace App\Repository;

use App\Entity\Damage;
use App\Entity\DamageRequest;
use App\Entity\DamageStatus;
use App\Entity\User;
use App\Entity\UserIdentity;
use App\Utils\Constants;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DamageRequest>
 *
 * @method DamageRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method DamageRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method DamageRequest[]    findAll()
 * @method DamageRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DamageRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DamageRequest::class);
    }

    /**
     * @param UserIdentity $user
     * @return array
     */
    public function getDamageRequestDetails(UserIdentity $user): array
    {
        $arrayResult = [];
        $queryResult = $this->createQueryBuilder('c')
            ->where('c.company = :company')
            ->setParameters(['company' => $user])
            ->getQuery()
            ->getResult();
        foreach ($queryResult as $key => $result) {
            $data['publicId'] = $result->getPublicId();
            $data['damage']['publicId'] = $result->getDamage()->getPublicId();
            $data['damage']['title'] = $result->getDamage()->getTitle();
            $data['damage']['description'] = $result->getDamage()->getDescription();
            $data['damage']['status'] = $result->getDamage()->getStatus()->getKey();
            $data['company']['publicId'] = $result->getCompany()->getPublicId();
            $data['company']['firstName'] = $result->getCompany()->getFirstName();
            $data['company']['lastName'] = $result->getCompany()->getLastName();
            $data['company']['companyName'] = $result->getCompany()->getCompanyName();
            $data['createdAt'] = $result->getCreatedAt();
            $data['updatedAt'] = $result->getUpdatedAt();
            $data['requestedDate'] = $result->getRequestedDate();
            $data['newOfferRequestedDate'] = $result->getNewOfferRequestedDate();
            array_push($arrayResult, $data);
        }
        return $arrayResult;
    }

    /**
     * @param DamageRequest $damageRequest
     * @param DamageStatus $damageStatus
     */
    public function updateDamageRequestStatusToClose(DamageRequest $damageRequest, DamageStatus $damageStatus): void
    {
        $this->createQueryBuilder('r')
            ->update()
            ->set('r.status', ':status')
            ->where('r.damage = :damage AND r.identifier != :identifier')
            ->setParameters(['status' => $damageStatus, 'damage' => $damageRequest->getDamage(),
                'identifier' => $damageRequest->getIdentifier()])
            ->getQuery()
            ->execute();
    }

    /**
     * @param Damage $damage
     */
    public function markRequestAsDeleted(Damage $damage): void
    {
        $this->createQueryBuilder('r')
            ->update()
            ->set('r.deleted', ':deleted')
            ->where('r.damage = :damage')
            ->setParameters(['deleted' => true, 'damage' => $damage])
            ->getQuery()
            ->execute();
    }

    /**
     * @param UserIdentity $company
     * @param Damage $damage
     * @param int $requestId
     */
    public function updateNewOfferRequestDate(UserIdentity $company, Damage $damage, int $requestId): void
    {
        $this->createQueryBuilder('r')
            ->update()
            ->set('r.newOfferRequestedDate', ':newOfferRequestedDate')
            ->where('r.damage = :damage AND r.company = :company AND r.identifier != :requestId AND r.deleted = :deleted')
            ->setParameters(['deleted' => false, 'damage' => $damage,
                'newOfferRequestedDate' => new \DateTime('now'), 'company' => $company, 'requestId' => $requestId])
            ->getQuery()
            ->execute();
    }

    /**
     * @param array $params
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findDamageRequest(array $params): ?DamageRequest
    {
        $params += ['deleted' => 0];
        return $this->createQueryBuilder('r')
            ->where('r.damage = :damage AND (r.company = :company OR r.companyEmail = :companyEmail)
             AND r.deleted = :deleted AND r.newOfferRequestedDate IS NULL AND r.requestRejectDate IS NULL')
            ->setParameters($params)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @param int $damage
     * @param string|null $company
     * @return array
     */
    public function getDamageRequests(int $damage, ?string $company = null): array
    {
        $data = [];
        $params = ['damage' => $damage, 'deleted' => 0];
        $andWhere = '';
        if (!is_null($company)) {
            $params += ['company' => $company];
            $andWhere = 'AND c.publicId = :company';
        }
        $query = $this->createQueryBuilder('r')
            ->select('c.identifier', 's.key')
            ->join('r.company', 'c')
            ->join('r.status', 's')
            ->where("r.damage = :damage AND r.deleted = :deleted $andWhere")
            ->setParameters($params);
        $results = $query->getQuery()->getResult();
        if (!empty($results)) {
            foreach ($results as $result) {
                $data[$result['identifier']] = $result['key'];
            }
        }
        return $data;
    }
}