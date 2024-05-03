<?php

namespace App\Service;

use App\Utils\ValidationUtility;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Uuid as UuidConstraint;
use App\Utils\ContainerUtility;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Utils\GeneralUtility;
use App\Entity\PropertyGroup;
use App\Entity\UserIdentity;
use App\Entity\Property;

/**
 * Class PropertyGroupService
 * @package App\Service
 */
class PropertyGroupService
{
    /**
     * @var ManagerRegistry $doctrine
     */
    private ManagerRegistry $doctrine;

    /**
     * @var ContainerUtility $containerUtility
     */
    private ContainerUtility $containerUtility;

    /**
     * @var GeneralUtility $generalUtility
     */
    private GeneralUtility $generalUtility;

    /**
     * @var ParameterBagInterface $params
     */
    private ParameterBagInterface $params;

    /**
     * UserService constructor.
     * @param ManagerRegistry $doctrine
     * @param ContainerUtility $containerUtility
     * @param ParameterBagInterface $params
     */
    public function __construct(ManagerRegistry $doctrine, ContainerUtility $containerUtility, ParameterBagInterface $params, GeneralUtility $generalUtility)
    {
        $this->doctrine = $doctrine;
        $this->containerUtility = $containerUtility;
        $this->params = $params;
        $this->generalUtility = $generalUtility;
    }

    /**
     * Method to create/update property group
     *
     * @param UserIdentity $user
     * @param Request $request
     * @param PropertyGroup|null $propertyGroup
     * @return PropertyGroup
     * @throws \Exception
     */
    public function createUpdatePropertyGroup(UserIdentity $user, Request $request, PropertyGroup $propertyGroup = null): PropertyGroup
    {
        $propertyGroupObj = (null === $propertyGroup) ? new PropertyGroup() : $propertyGroup;

        return $this->containerUtility->convertRequestKeysToSetters(['createdBy' => $user, 'name' => $request->request->get('name')], $propertyGroupObj);
    }

    /**
     * Method to delete property group
     *
     * @param PropertyGroup $propertyGroup
     * @return bool
     */
    public function deletePropertyGroup(PropertyGroup $propertyGroup): bool
    {
        $propertyGroup->setDeleted(1);
        $em = $this->doctrine->getManager();
        $mappings = $propertyGroup->getProperties();
        if (!empty($mappings)) {
            foreach ($mappings as $mapping) {
                $mapping->removePropertyGroup($propertyGroup);
                $em->persist($mapping);
            }
        }
        return true;
    }

    /**
     *
     * @param UserIdentity $user
     * @return array
     */
    public function getUniqueGroups(UserIdentity $user): array
    {
        $em = $this->doctrine->getManager();
        $finalList = $ownerGroups = [];
        $properties = $em->getRepository(Property::class)->findBy(['deleted' => 0, 'administrator' => $user]);
        foreach ($properties as $key => $property) {
            $ownerGroups = $em->getRepository(PropertyGroup::class)->findBy(['deleted' => 0, 'createdBy' => $property->getUser()]);
            foreach ($ownerGroups as $ownerGroup) {
                $finalList[] = $ownerGroup;
            }
        }
        $groups = $em->getRepository(PropertyGroup::class)->findBy(['deleted' => 0, 'createdBy' => $user]);
        foreach ($groups as $group) {
            $finalList[] = $group;
        }
        $uniqueGroups = [];
        foreach ($finalList as $value) {
            if (!in_array($value, $uniqueGroups)) {
                $uniqueGroups[] = $value;
            }
        }

        return $uniqueGroups;
    }
}


