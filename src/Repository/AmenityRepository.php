<?php

namespace App\Repository;

use App\Entity\Amenity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Traits\RepositoryTrait;

/**
 * @method Amentity|null find($id, $lockMode = null, $lockVersion = null)
 * @method Amentity|null findOneBy(array $criteria, array $orderBy = null)
 * @method Amentity[]    findAll()
 * @method Amentity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AmenityRepository extends ServiceEntityRepository
{
    use RepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Amenity::class);
    }

    /**
     * get all amenities with pagination
     *
     * @param string $locale
     * @param string|null $sortBy
     * @param string|null $sortOrder
     * @param int|null $count
     * @param int|null $startPage
     * @return array
     */
    public function getAmenities(string $locale, ?string $sortBy = null, ?string $sortOrder = 'ASC', ?int $count = null, ?int $startPage = null): array
    {
        $locale = ($locale === 'de') ? ucfirst($locale) : '';
        $qb = $this->createQueryBuilder('a');
        $qb->select("a.publicId, a.name$locale as name, a.sortOrder, a.amenityKey as key, a.isInput")
            ->where('a.active = :active')
            ->andWhere('a.deleted = :deleted')
            ->setParameters(['active' => 1, 'deleted' => 0]);
        if (null === $sortBy) {
            $sortBy = 'a.sortOrder';
        }
        if (!($sortOrder == 'ASC' || $sortOrder == 'DESC')) {
            $sortOrder = 'ASC';
        }
        $qb->orderBy($sortBy, $sortOrder);

        return $this->handlePagination($qb, $startPage, $count);
    }
}
