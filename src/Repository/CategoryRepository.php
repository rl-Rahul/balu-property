<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Damage;
use App\Entity\DamageRequest;
use App\Entity\DamageStatus;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr;

/**
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @param array $parameters
     * @param string $locale
     * @return array
     */
    public function getCategories(array $parameters, string $locale): array
    {
        $name = ($locale === 'de') ? 'c.nameDe AS name' : 'c.name AS name';
        $toSelect = ['c.publicId', $name];
        return $this->createQueryBuilder('c')
            ->select($toSelect)
            ->andWhere('c.active = :active')
            ->andWhere('c.deleted = :deleted')
            ->setParameters($parameters)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * get company details
     *
     * @param array $param
     * @return int|mixed|string
     */
    public function getCompanies(array $param): array
    {
        $data = [];
        $company = $this->findOneBy(['publicId' => $param['category']]);
        $companyRejectStatus = $this->_em->getRepository(DamageStatus::class)->findOneBy(['key' => 'COMPANY_REJECT_THE_DAMAGE']);
        $params = ['category' => $company->getIdentifier()];
        $damage = $this->_em->getRepository(Damage::class)->findOneBy(['publicId' => $param['damage']]);
        $query = $this->createQueryBuilder('c')
            ->select('ui.publicId, ui.firstName, ui.lastName, ui.companyName, u.property as email, c.icon, ui.identifier AS company,
             c.name AS catName, c.publicId AS catId')
            ->join('c.user', 'ui')
            ->join('ui.user', 'u')
            ->where('c.identifier = :category');
        if ($damage instanceof Damage) {
            $query->leftJoin(DamageRequest::class, 'r', Expr\Join::WITH, 'ui.identifier = r.company AND r.damage = :damage AND r.deleted != :deleted')
                ->addSelect('CASE WHEN r.company = ui.identifier THEN true ELSE false END AS isAlreadyAssigned');
            $params += ['damage' => $damage->getIdentifier(), 'deleted' => 1];
        }
        $query->setParameters($params);
        $query->groupBy('company');
        $results = $query->getQuery()->getResult();

        foreach ($results as $result) {
            $result['icon'] = $param['iconPath'] . $result['icon'];
            $result['isAlreadyAssigned'] = isset($result['isAlreadyAssigned']) && $result['isAlreadyAssigned'] === '1';
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
            $result['category'] = ['publicId' => $result['catId'], 'name' => $result['catName']];
            unset($result['catId'], $result['catName'], $result['company']);
            $data[] = $result;
        }

        return $data;
    }
}
