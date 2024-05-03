<?php

namespace App\DataFixtures;

use App\Entity\NoticePeriod;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use App\Utils\Constants;

/**
 * Class NoticePeriodFixture
 * @package App\DataFixtures
 */
class NoticePeriodFixture extends Fixture implements FixtureGroupInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $types = ['1 month', '3 months', '6 months'];
        $typesDe = ['1 Monat', '3 Monate', '6 Monate'];
        $periodTypes = [Constants::TYPE_NOTICE_PERIOD_1_MONTH, Constants::TYPE_NOTICE_PERIOD_3_MONTH, Constants::TYPE_NOTICE_PERIOD_6_MONTH];
        foreach($types as $key => $type){
            $rentalTypes = new NoticePeriod();
            $rentalTypes->setNameEn($type)
                        ->setNameDe($typesDe[$key])
                        ->setType($periodTypes[$key]);
            $manager->persist($rentalTypes);
        }
       
        $manager->flush();
    }

    /**
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['notice_group'];
    }
}
