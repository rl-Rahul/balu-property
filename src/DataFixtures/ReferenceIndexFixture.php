<?php

namespace App\DataFixtures;

use App\Entity\ReferenceIndex;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * Class ReferenceIndexFixture
 * @package App\DataFixtures
 */
class ReferenceIndexFixture extends Fixture implements FixtureGroupInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $types = ['0.00 %', '0.01 %',];
        foreach($types as $key => $type){
            $refences = new ReferenceIndex();
            $refences->setName($type)
                    ->setActive(1);
            $manager->persist($refences);
        }
       
        $manager->flush();
    }

    /**
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['reference_group'];
    }
}
