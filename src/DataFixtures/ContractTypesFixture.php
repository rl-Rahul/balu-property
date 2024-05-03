<?php

namespace App\DataFixtures;

use App\Entity\ContractTypes;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * Class ContractTypesFixture
 * @package App\DataFixtures
 */
class ContractTypesFixture extends Fixture implements FixtureGroupInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $types = ['Rental', 'Ownership'];
        $typesDe = ['Vermietung', 'Eigentümerschaft'];
        foreach($types as $key => $type){
            $contractTypes = new ContractTypes();
            $contractTypes->setNameEn($type)
                            ->setNameDe($typesDe[$key])
                            ->setType($key);
            $manager->persist($contractTypes);
        }
       
        $manager->flush();
    }

    /**
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['contract_group'];
    }
}
