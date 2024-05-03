<?php


namespace App\Service;


use App\Entity\Floor;
use App\Entity\LandIndex;
use App\Entity\ObjectTypes;
use App\Entity\ReferenceIndex;
use App\Utils\Constants;
use App\Utils\ContainerUtility;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

class MasterService extends BaseService
{
    /**
     * @var ManagerRegistry $doctrine
     */
    private ManagerRegistry $doctrine;
    /**
     * @var ContainerUtility
     */
    private ContainerUtility $containerUtility;

    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * MasterService constructor.
     * @param ManagerRegistry $doctrine
     * @param ContainerUtility $containerUtility
     * @param TranslatorInterface $translator
     */
    public function __construct(ManagerRegistry $doctrine, ContainerUtility $containerUtility, TranslatorInterface $translator)
    {
        $this->doctrine = $doctrine;
        $this->containerUtility = $containerUtility;
        $this->translator = $translator;
    }

    /**
     * @param array $requestArray
     * @return bool
     * @throws \Exception
     */
    public function saveFloorData(array $requestArray): bool
    {
        $floor = new Floor();
        $this->containerUtility->convertRequestKeysToSetters($requestArray, $floor);

        return true;
    }

    /**
     * @param Floor $floor
     * @param array $requestArray
     * @return array
     */
    public function updateFloorData(Floor $floor, array $requestArray): array
    {
        $em = $this->doctrine->getManager();
        $floor->setFloorNumber($requestArray['floorNumber']);
        $floor->setSortOrder($requestArray['sortOrder']);
        $floor->setUpdatedAt(new \DateTime('now'));
        $floor->setDeleted(false);
        $em->persist($floor);
        $em->flush();

        $response['publicId'] = $floor->getPublicId();
        $response['floorNumber'] = $floor->getFloorNumber();

        return $response;
    }

    /**
     * @param Floor $floor
     * @return array
     */
    public function getFloorData(Floor $floor): array
    {
        $response['publicId'] = $floor->getPublicId();
        $response['floorNumber'] = $floor->getFloorNumber();
        $response['sortOrder'] = $floor->getSortOrder();
        $response['createdAt'] = $floor->getCreatedAt();
        $response['updatedAt'] = $floor->getUpdatedAt();

        return $response;
    }

    /**
     * @param array $requestArray
     * @return bool
     * @throws \Exception
     */
    public function saveLandIndexData(array $requestArray): bool
    {
        $landIndex = new LandIndex();
        $requestArray['sortOrder'] = isset($requestArray['sortOrder']) ? $requestArray['sortOrder'] : null;
        $requestArray['active'] = isset($requestArray['active']) ? $requestArray['active'] : true;
        $this->containerUtility->convertRequestKeysToSetters($requestArray, $landIndex);

        return true;
    }

    /**
     * @param LandIndex $landIndex
     * @param array $requestArray
     * @return array
     */
    public function updateLandIndex(LandIndex $landIndex, array $requestArray): array
    {
        $em = $this->doctrine->getManager();
        $landIndex->setName($requestArray['name']);
        $landIndex->setNameDe($requestArray['nameDe']);
        $landIndex->setUpdatedAt(new \DateTime('now'));
        $landIndex->setDeleted(false);
        isset($requestArray['sortOrder']) ? $landIndex->setSortOrder($requestArray['sortOrder']) : '';
        isset($requestArray['active']) ? $landIndex->setActive($requestArray['active']) : '';
        $em->persist($landIndex);
        $em->flush();

        $response['publicId'] = $landIndex->getPublicId();
        $response['name'] = $landIndex->getName();
        $response['nameDe'] = $landIndex->getNameDe();
        $response['sortOrder'] = $landIndex->getSortOrder();
        $response['active'] = $landIndex->getActive();

        return $response;
    }

    /**
     * @param LandIndex $landIndex
     * @return array
     */
    public function getLandIndexData(LandIndex $landIndex): array
    {
        $response['publicId'] = $landIndex->getPublicId();
        $response['name'] = $landIndex->getName();
        $response['nameDe'] = $landIndex->getNameDe();
        $response['sortOrder'] = $landIndex->getSortOrder();
        $response['active'] = $landIndex->getActive();
        $response['createdAt'] = $landIndex->getCreatedAt();
        $response['updatedAt'] = $landIndex->getUpdatedAt();

        return $response;
    }

    /**
     * @param array $requestArray
     * @return bool
     * @throws \Exception
     */
    public function saveObjectTypeData(array $requestArray): bool
    {

        $requestArray['sortOrder'] = isset($requestArray['sortOrder']) ? $requestArray['sortOrder'] : null;
        $requestArray['active'] = isset($requestArray['active']) ? $requestArray['active'] : true;
        $objectTypes = new ObjectTypes();
        $this->containerUtility->convertRequestKeysToSetters($requestArray, $objectTypes);

        return true;
    }

    /**
     * @param ObjectTypes $objectTypes
     * @param array $requestArray
     * @return array
     */
    public function updateObjectType(ObjectTypes $objectTypes, array $requestArray): array
    {
        $em = $this->doctrine->getManager();
        $objectTypes->setName($requestArray['name']);
        $objectTypes->setNameDe($requestArray['nameDe']);
        $objectTypes->setUpdatedAt(new \DateTime('now'));
        $objectTypes->setDeleted(false);
        isset($requestArray['sortOrder']) ? $objectTypes->setSortOrder($requestArray['sortOrder']) : '';
        isset($requestArray['active']) ? $objectTypes->setActive($requestArray['active']) : '';
        $em->persist($objectTypes);
        $em->flush();

        $response['publicId'] = $objectTypes->getPublicId();
        $response['name'] = $objectTypes->getName();
        $response['nameDe'] = $objectTypes->getNameDe();
        $response['sortOrder'] = $objectTypes->getSortOrder();
        $response['active'] = $objectTypes->getActive();

        return $response;
    }

    /**
     * @param ObjectTypes $objectTypes
     * @return array
     */
    public function getObjectTypeData(ObjectTypes $objectTypes): array
    {
        $response['publicId'] = $objectTypes->getPublicId();
        $response['name'] = $objectTypes->getName();
        $response['nameDe'] = $objectTypes->getNameDe();
        $response['sortOrder'] = $objectTypes->getSortOrder();
        $response['active'] = $objectTypes->getActive();
        $response['createdAt'] = $objectTypes->getCreatedAt();
        $response['updatedAt'] = $objectTypes->getUpdatedAt();

        return $response;
    }

    /**
     * @param array $requestArray
     * @return bool
     * @throws \Exception
     */
    public function saveReferenceIndexData(array $requestArray): bool
    {
        $requestArray['sortOrder'] = isset($requestArray['sortOrder']) ? $requestArray['sortOrder'] : null;
        $requestArray['active'] = isset($requestArray['active']) ? $requestArray['active'] : true;
        $referenceIndex = new ReferenceIndex();
        $this->containerUtility->convertRequestKeysToSetters($requestArray, $referenceIndex);

        return true;
    }

    /**
     * @param ReferenceIndex $referenceIndex
     * @param array $requestArray
     * @return array
     */
    public function updateReferenceIndex(ReferenceIndex $referenceIndex, array $requestArray): array
    {
        $em = $this->doctrine->getManager();
        $referenceIndex->setName($requestArray['name']);
        $referenceIndex->setUpdatedAt(new \DateTime('now'));
        $referenceIndex->setDeleted(false);
        isset($requestArray['sortOrder']) ? $referenceIndex->setSortOrder($requestArray['sortOrder']) : '';
        isset($requestArray['active']) ? $referenceIndex->setActive($requestArray['active']) : '';
        $em->persist($referenceIndex);
        $em->flush();

        $response['publicId'] = $referenceIndex->getPublicId();
        $response['name'] = $referenceIndex->getName();
        $response['sortOrder'] = $referenceIndex->getSortOrder();
        $response['active'] = $referenceIndex->getActive();

        return $response;
    }

    /**
     * @param ReferenceIndex $referenceIndex
     * @return array
     */
    public function getReferenceIndexData(ReferenceIndex $referenceIndex): array
    {
        $response['publicId'] = $referenceIndex->getPublicId();
        $response['name'] = $referenceIndex->getName();
        $response['sortOrder'] = $referenceIndex->getSortOrder();
        $response['active'] = $referenceIndex->getActive();
        $response['createdAt'] = $referenceIndex->getCreatedAt();
        $response['updatedAt'] = $referenceIndex->getUpdatedAt();

        return $response;
    }

    /**
     * @param string $locale
     * @return array
     */
    public function getMasterDataTypes(string $locale): array
    {
        $availableTypes = Constants::SUPER_ADMIN_MASTER_DATA_TYPES;
        $formattedResult = [];
        foreach ($availableTypes as $eachType) {
            $result['type'] = $eachType;
            $result['name'] = $this->translator->trans($eachType, [], null, $locale);
            $formattedResult[] = $result;
        }

        return $formattedResult;
    }

    /**
     * @param string $type
     * @param string $locale
     * @param array $params
     * @return array
     * @throws EntityNotFoundException
     */
    public function getMasterDataBasedOnTypes(string $type, string $locale, array $params): array
    {
        switch ($type) {
            case Constants::MASTER_DATA_KEY_FLOOR:
                return $this->doctrine->getRepository(Floor::class)->findAllBy($locale, true, $params);
            case Constants::MASTER_DATA_KEY_LAND_INDEX:
                return $this->doctrine->getRepository(LandIndex::class)->findAllBy($locale, true, $params);
            case Constants::MASTER_DATA_KEY_OBJECT_TYPE:
                return $this->doctrine->getRepository(ObjectTypes::class)->findAllBy($locale, true, $params);
            case Constants::MASTER_DATA_KEY_REFERENCE_TYPE:
                return $this->doctrine->getRepository(ReferenceIndex::class)->findAllBy($locale, true, $params);
            default:
                throw new EntityNotFoundException('entityNotFound');
        }
    }
}