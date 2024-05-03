<?php

namespace App\DataFixtures;

use App\Entity\RentalTypes;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * Class RentalTypeFixture
 * @package App\DataFixtures
 */
class RentalTypeFixture extends Fixture implements FixtureGroupInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $types = ['open-end', 'fixed-term'];
        $typesDe = ['offenes Ende', 'Befristet'];
        foreach($types as $key => $type){
            $rentalTypes = new RentalTypes();
            $rentalTypes->setName($type)
                    ->setNameDe($typesDe[$key])
                    ->setType($type);
            $manager->persist($rentalTypes);
        }
       
        $manager->flush();
    }

    /**
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['rental_group'];
    }
}
