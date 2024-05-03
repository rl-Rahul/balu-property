<?php

namespace App\DataFixtures;

use App\Entity\Floor;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * Class FloorFixture
 * @package App\DataFixtures
 */
class FloorFixture extends Fixture implements FixtureGroupInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $floors = ['2', '1', '0 (ground floor)', '-1', '-2'];
        foreach($floors as $floor){
            $floorObj = new Floor();
            $floorObj->setFloorNumber($floor);
            $manager->persist($floorObj);
        }
       
        $manager->flush();
    }

    /**
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['floor_group'];
    }
}
