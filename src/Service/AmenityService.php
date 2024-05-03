<?php


namespace App\Service;

use App\Entity\Amenity;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use App\Utils\ContainerUtility;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Utils\GeneralUtility;

/**
 * AmenityService
 *
 * Amenity service actions.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class AmenityService
{
    /**
     * @var ManagerRegistry $doctrine
     */
    private ManagerRegistry $doctrine;

    /**
     * UserService constructor.
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Get all active amenities
     *
     * @param Request $request
     * @param string $locale
     * @return array
     */
    public function getAmenities(Request $request, string $locale): array
    {
        $em = $this->doctrine->getManager();
        return $em->getRepository(Amenity::class)->getAmenities($locale, $request->get('sort'), $request->get('sortOrder'), $request->get('count'), $request->get('page'));
    }
}
