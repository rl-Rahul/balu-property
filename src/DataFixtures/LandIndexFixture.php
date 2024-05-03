<?php

namespace App\DataFixtures;

use App\Entity\LandIndex;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * Class LandIndexFixture
 * @package App\DataFixtures
 */
class LandIndexFixture extends Fixture implements FixtureGroupInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $types = ['December 2015 = 100', 'December 2010 = 100'];
        $typesDe = ['Dezember 2015 = 100', 'Dezember 2010 = 100'];
        foreach($types as $key => $type){
            $land = new LandIndex();
            $land->setName($type)
                    ->setNameDe($typesDe[$key])
                    ->setActive(1);
            $manager->persist($land);
        }
       
        $manager->flush();
    }

    /**
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['land_group'];
    }
}
