<?php

namespace App\Repository;

use App\Entity\Permission;
use App\Entity\Role;
use App\Entity\Temp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MigrationRepository|null find($id, $lockMode = null, $lockVersion = null)
 * @method MigrationRepository|null findOneBy(array $criteria, array $orderBy = null)
 * @method MigrationRepository[]    findAll()
 * @method MigrationRepository[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MigrationRepository extends ServiceEntityRepository
{
    /**
     * MigrationRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Temp::class);
    }

    /**
     * @param array $values
     * @param array $toUnset
     * @return array
     */
    private function unsetArrayValues(array $values, array $toUnset): array
    {
        if (!empty($toUnset)) {
            foreach ($toUnset as $value) {
                unset($values[$value]);
            }
        }
        return $values;
    }

    /**
     * @param array $values
     * @param array $toUnset
     * @param array $replaceWith
     * @return array
     */
    private function unsetAndReplaceArrayKeys(array $values, array $toUnset, array $replaceWith = []): array
    {
        if (!empty($replaceWith)) {
            foreach ($replaceWith as $key => $item) {
                $values[$item] = $values[$key];
            }
        }
        return $this->unsetArrayValues($values, $toUnset);
    }

    /**
     * @param $string
     * @param false $capitalizeFirstCharacter
     * @return string|string[]
     */
    private function underScoreToCamelCase($string, $capitalizeFirstCharacter = false)
    {
        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
        if (!$capitalizeFirstCharacter) {
            $str[0] = strtolower($str[0]);
        }
        return $str;
    }

    /**
     * @param array $objectTypes
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function insertObjectTypes(array $objectTypes): void
    {
        $this->insertData($objectTypes, 'ObjectTypes', [], 'id', 'name_ar');
    }

    /**
     * @param array $categories
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function insertCategories(array $categories): void
    {
        $this->insertData($categories, 'Category', [], 'id', 'name_ar', 'created_at', 'updated_at');
    }

    /**
     * @param array $landIndices
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function insertLandIndices(array $landIndices): void
    {
        $this->insertData($landIndices, 'LandIndex', [], 'id', 'name_ar');
    }

    /**
     * @param array $roles
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function insertRoles(array $roles): void
    {
        $this->insertData($roles, 'Role', ['key' => 'roleKey'], 'id', 'key');
    }

    /**
     * @param array $plans
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function insertCompanySubscriptionPlan(array $plans): void
    {
        $this->insertData($plans, 'CompanySubscriptionPlan', [], 'id', 'created_at', 'updated_at');
    }

    /**
     * @param array $permissions
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function insertPermissions(array $permissions): void
    {
        $this->insertData($permissions, 'Permission', ['key' => 'permissionKey'], 'id', 'key');
    }

    /**
     * @param array $plans
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function insertSubscriptionPlans(array $plans): void
    {
        $this->insertData($plans, 'SubscriptionPlan', [], 'id', 'created_at', 'updated_at');
    }

    /**
     * @param array $referenceIndices
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function insertReferenceIndex(array $referenceIndices): void
    {
        $this->insertData($referenceIndices, 'ReferenceIndex', [], 'id');
    }

    /**
     * @param array $data
     * @param string $class
     * @param array $replaceWith
     * @param mixed ...$toUnset
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function insertData(array $data, string $class, array $replaceWith = [], ...$toUnset): void
    {
        foreach ($data as $type) {
            $className = "App\\Entity\\" . $class;
            $object = new $className();
            $type = $this->unsetAndReplaceArrayKeys($type, $toUnset, $replaceWith);
            foreach ($type as $key => $value) {
                $method = 'set' . $this->underScoreToCamelCase($key, true);
                $object->$method($value);
            }
            $this->_em->persist($object);
        }
        $this->_em->flush();
    }

    /**
     * @param array $data
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function insertRolePermission(array $data): void
    {
        foreach ($data as $datum) {
            $role = $this->_em->getRepository(Role::class)->findOneBy(['roleKey' => $datum['key']]);
            $permission = $this->_em->getRepository(Permission::class)->findOneBy(['permissionKey' => $datum['permissionKey']]);
            $role->addPermission($permission);
        }
        $this->_em->flush();
    }
}
