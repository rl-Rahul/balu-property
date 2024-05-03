<?php

namespace App\DataFixtures;

use App\Entity\Currency;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * Class CurrencyFixture
 * @package App\DataFixtures
 */
class CurrencyFixture extends Fixture implements FixtureGroupInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $types = ['CHF'];
        $typesDe = ['CHF'];
        foreach($types as $key => $type){
            $rentalTypes = new Currency();
            $rentalTypes->setNameEn($type)
                            ->setNameDe($typesDe[$key]);
            $manager->persist($rentalTypes);
        }
       
        $manager->flush();
    }

    /**
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['currency_group'];
    }
}
