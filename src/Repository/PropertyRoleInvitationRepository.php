<?php

namespace App\Repository;

use App\Entity\Property;
use App\Entity\PropertyRoleInvitation;
use App\Entity\Role;
use App\Entity\UserIdentity;
use App\Utils\Constants;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PropertyRoleInvitation>
 *
 * @method PropertyRoleInvitation|null find($id, $lockMode = null, $lockVersion = null)
 * @method PropertyRoleInvitation|null findOneBy(array $criteria, array $orderBy = null)
 * @method PropertyRoleInvitation[]    findAll()
 * @method PropertyRoleInvitation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PropertyRoleInvitationRepository extends ServiceEntityRepository
{
    /**
     * PropertyRoleInvitationRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyRoleInvitation::class);
    }

    /**
     * @param UserIdentity $invitee
     * @param UserIdentity $invitor
     * @param Role $role
     * @param Property $property
     */
    public function createPropertyRoleInvitation(UserIdentity $invitee, UserIdentity $invitor, Role $role,
                                                 Property $property): void
    {
        $propertyRoleInvitation = new PropertyRoleInvitation();
        $propertyRoleInvitation->setCreatedAt(new \DateTime('now'));
        $propertyRoleInvitation->setProperty($property);
        $propertyRoleInvitation->setInvitee($invitee);
        $propertyRoleInvitation->setInvitor($invitor);
        $propertyRoleInvitation->setRole($role);
        $propertyRoleInvitation->setDeleted(false);
        $this->_em->persist($propertyRoleInvitation);
        $this->_em->flush();
    }

    /**
     * removeOldInvitations
     *
     * @param PropertyRoleInvitation $propertyRoleInvitation
     */
    public function removeOldInvitations(PropertyRoleInvitation $propertyRoleInvitation): void
    {
        $this->createQueryBuilder('i')
            ->update()
            ->set('i.deleted', ':deleted')
            ->where('i.property = :property AND i.role = :role AND i.identifier != :identifier')
            ->setParameters(['deleted' => true, 'property' => $propertyRoleInvitation->getProperty(),
                'role' => $propertyRoleInvitation->getRole(), 'identifier' => $propertyRoleInvitation->getIdentifier()])
            ->getQuery()
            ->execute();
    }

    /**
     * findPropertyAdmin
     *
     * @param int $property
     * @param string $roleKey
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findPropertyAdmin(int $property, string $roleKey): array
    {
        $query = $this->createQueryBuilder('pr')
            ->select('IDENTITY(pr.invitee) as invitee')
            ->join('pr.role', 'r')
            ->where('pr.property = :property AND r.roleKey = :roleKey AND pr.invitationAcceptedDate IS NOT NULL')
            ->setParameters(['property' => $property, 'roleKey' => $roleKey])
            ->getQuery()->getResult();
        return array_unique(array_column($query, 'invitee'));
    }

    /**
     * checkJanitorInvitationStatus
     *
     * @param int $property
     * @param string $roleKey
     * @param UserIdentity $user
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function checkJanitorInvitationStatus(int $property, string $roleKey, UserIdentity $user): ?PropertyRoleInvitation
    {
        $query = $this->createQueryBuilder('pr')
            ->join('pr.role', 'r')
            ->where('pr.property = :property AND r.roleKey = :roleKey AND pr.invitee = :user AND pr.deleted = :deleted')
            ->setParameters(['property' => $property, 'roleKey' => $roleKey, 'user' => $user->getIdentifier(), 'deleted' => false]);
        return $query->getQuery()->getOneOrNullResult();
    }
}
