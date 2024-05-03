<?php

namespace App\DataFixtures;

use App\Entity\ModeOfPayment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * Class ModeOfPaymentFixture
 * @package App\DataFixtures
 */
class ModeOfPaymentFixture extends Fixture implements FixtureGroupInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $types = ['Monthly', 'Quarterly', 'Half yearly', 'Yearly'];
        $typesDe = ['Monatlich', 'Vierteljährlich', 'Halbjährlich', 'Jährlich'];
        foreach($types as $key => $type){
            $rentalTypes = new ModeOfPayment();
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
        return ['payment_group'];
    }
}
