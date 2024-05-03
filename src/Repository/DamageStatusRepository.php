<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Repository;

use App\Entity\DamageStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * DamageStatusRepository
 * Repository used for Damage Status related queries
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 *
 *
 */
class DamageStatusRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DamageStatus::class);
    }

    /**
     * get damage status based on user roles
     *
     * @param string $role
     * @return array
     */
    public function getDamageStatus(string $role)
    {
        $qb = $this->createQueryBuilder('s');
        $query = $qb->select('s.key')
            ->where('s.key Like :role')
            ->andWhere('s.active = :active')
            ->setParameters(array('role' => $role . '%', 'active' => 1));

        return array_column($query->getQuery()->getResult(), 'key');
    }
}
