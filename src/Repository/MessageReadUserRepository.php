<?php

namespace App\Repository;

use App\Entity\MessageReadUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MessageReadUser>
 *
 * @method MessageReadUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method MessageReadUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method MessageReadUser[]    findAll()
 * @method MessageReadUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageReadUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageReadUser::class);
    }

    public function add(MessageReadUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MessageReadUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
