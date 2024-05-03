<?php

namespace App\DataFixtures;

use App\Entity\Amenity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * Class AmenityFixture
 * @package App\DataFixtures
 */
class AmenityFixture extends Fixture implements FixtureGroupInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $features = [ 'name' => ['Balcony / Terrace / Loggia', 'Fireplace', 'Wheelchair accessible', 'Elevator', 'Master key system', 'Kitchen (equipped)', 'Separate toilet', 'Shower / toilet', 'Bathtub / toilet', 'Sink'] , 
            'nameDe' => ['Balkon / Terrasse / Loggia', 'Kamin', 'Zugänglich für Rollstuhlfahrer', 'Aufzug', 'Hauptschlüssel-System', 'Küche (ausgestattet)', 'Getrennte Toilette', 'Dusche / Toilette', 'Badewanne / Toilette', 'Spülbecken'],
            'key' => ['bal', 'fir', 'whe', 'ele', 'mks', 'kit', 'set', 'sho', 'bat', 'sin'],
            'isInput' => [1, 0, 0, 0, 1, 0, 1, 1, 1, 1]
            ];
        foreach($features['name'] as $key => $feature){
            $objectTypeFeature = new Amenity();
            $objectTypeFeature->setName($feature)
                            ->setNameDe($features['nameDe'][$key])
                            ->setAmenityKey($features['key'][$key])
                            ->setIsInput($features['isInput'][$key])
                            ->setActive(1);
            $manager->persist($objectTypeFeature);
        }
        $manager->flush();
    }

    /**
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['amenity_group'];
    }
}
