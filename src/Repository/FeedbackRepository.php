<?php

namespace App\Repository;

use App\Entity\Feedback;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FeedbackRepository extends ServiceEntityRepository
{
    /**
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feedback::class);
    }

    /**
     * @param array $params
     * @return int|mixed|string
     */
    public function getFeedbackDetails(array $params)
    {
        $query = $this->createQueryBuilder('f');
        $query->where('f.deleted = :deleted')
            ->setParameter('deleted', false);

        if (isset($params['searchKey']) && !empty(trim($params['searchKey']))) {
            $query->andWhere("f.message LIKE :search OR f.subject LIKE :search")
                ->setParameter('search', '%' . $params['searchKey'] . '%');
        }
        $query->orderBy('f.createdAt', 'ASC');
        if (!empty($params['limit'])) {
            $query->setMaxResults($params['limit']);
        }
        if (!empty($params['offset'])) {
            $query->setFirstResult($params['offset']);
        }

        return $query->getQuery()->getResult();
    }

}
