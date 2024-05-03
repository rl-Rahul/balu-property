<?php

namespace App\DataFixtures;

use App\Entity\ObjectTypes;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * Class ObjectTypeFixture
 * @package App\DataFixtures
 */
class ObjectTypeFixture extends Fixture implements FixtureGroupInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $types = [
            'Flat',
            'Furnished Apartment',
            'Detached house',
            'Office / Practice rooms',
            'Warehouses',
            'Stock',
            'Work room',
            'Garage / Parking space',
            'Parking spot',
            'Holiday flat',
            'Holiday Home',
            'Room',
            'Property',
            'Camp',
            'House / Apartment',
            'Apartment',
            'Hobby Room / Store Room',
            'Parking Lot / Garage',
            'Furnished Apartment',
            'Furnished Office',
            'Office / Surgery Room',
            'Commercial / Industrial Room',
            'Farming',
            'Property Land',
            'General/Environment'
        ];
        $typesDe = [
            'Wohnung',
            'Möblierte Wohnung',
            'Freistehendes Haus',
            'Büro-/Praxisräume',
            'Lagerhäuser',
            'Lagerbestand',
            'Arbeitsraum',
            'Garage / Stellplatz',
            'Parkplatz',
            'Ferienwohnung',
            'Ferienhaus',
            'Zimmer',
            'Eigentum',
            'Camp',
            'Haus / Wohnung',
            'Wohnung',
            'Hobbyraum / Abstellraum',
            'Parkplatz/Garage',
            'Möblierte Wohnung',
            'Möbliertes Büro',
            'Büro / Operationssaal',
            'Gewerblicher / industrieller Raum',
            'Landwirtschaft',
            'Grundstücke Land',
            'Allgemein / Umgebung'
        ];
        foreach ($types as $key => $type) {
            $objectType = $manager->getRepository(ObjectTypes::class)->findOneBy(['name' => $type]);
            if (!$objectType instanceof ObjectTypes) {
                $rentalTypes = new ObjectTypes();
                $rentalTypes->setName($type)
                    ->setNameDe($typesDe[$key]);
                $manager->persist($rentalTypes);
            }
        }
       
        $manager->flush();
    }

    /**
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['object_type'];
    }
}
