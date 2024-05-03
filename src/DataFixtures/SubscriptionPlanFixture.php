<?php

namespace App\DataFixtures;

use App\Entity\SubscriptionPlan;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use App\Utils\Constants;

/**
 * Class SubscriptionPlanFixture
 * @package App\DataFixtures
 */
class SubscriptionPlanFixture extends Fixture implements FixtureGroupInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $plan = new SubscriptionPlan();
        $plan->setName('Balu Silver')
            ->setNameDe('Balu-Silber')
            ->setPeriod(1)
            ->setApartmentMax(10)
            ->setApartmentMin(3)
            ->setAmount(60)
            ->setActive(1)
            ->setStripePlan('price_1Mng2aFcdDSBW4LVGJeeffAk')
            ->setColorCode('#595959ba')
            ->setTextColor('#000000')
            ->setDetails(['description' => 'description_silver', 'features' => ['objectLimit', 'features', 'contractPeriod', 'cancellation']]);
        $manager->persist($plan);

        $plan = new SubscriptionPlan();
        $plan->setName('Balu Basic')
            ->setNameDe('Balu Basic')
            ->setPeriod(1)
            ->setApartmentMax(2)
            ->setApartmentMin(1)
            ->setAmount(10)
            ->setActive(1)
            ->setColorCode('#5595c2')
            ->setTextColor('#000000')
            ->setStripePlan('price_1Mng08FcdDSBW4LVjj8JXUlA')
            ->setDetails(['description' => 'description_basic', 'features' => ['objectLimit', 'features', 'contractPeriod', 'cancellation']]);
        $manager->persist($plan);

        $plan = new SubscriptionPlan();
        $plan->setName('Balu Gold')
            ->setNameDe('Balu-Gold')
            ->setPeriod(1)
            ->setApartmentMax(40)
            ->setApartmentMin(11)
            ->setAmount(180)
            ->setActive(1)
            ->setColorCode('#caa42b')
            ->setTextColor('#000000')
            ->setStripePlan('price_1Mng5eFcdDSBW4LVgxap8rZy')
            ->setDetails(['description' => 'description_gold', 'features' => ['objectLimit', 'features', 'contractPeriod', 'cancellation']]);

        $manager->persist($plan);

        $plan = new SubscriptionPlan();
        $plan->setName('Balu Platinum')
            ->setNameDe('Balu Platin')
            ->setPeriod(1)
            ->setApartmentMax(10000)
            ->setApartmentMin(1)
            ->setAmount(330)
            ->setActive(1)
            ->setColorCode('#363636')
            ->setTextColor('#ffffff')
            ->setStripePlan('price_1Mng6lFcdDSBW4LVFzaYitbn')
            ->setDetails(['description' => 'description_platinum', 'features' => ['platinumLimitation', 'features', 'contractPeriod', 'cancellation']]);
        $manager->persist($plan);

        $plan = new SubscriptionPlan();
        $plan->setName('Free Trial Version')
            ->setNameDe('Kostenlose Testversion')
            ->setPeriod(1)
            ->setApartmentMax(null)
            ->setApartmentMin(null)
            ->setAmount(null)
            ->setActive(1)
            ->setInitialPlan(1)
            ->setColorCode('#42c29b')
            ->setTextColor('#000000')
            ->setDetails(['description' => 'description_trial', 'features' => ['trialLimit', 'features']]);
        $manager->persist($plan);

        $manager->flush();
    }

    /**
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['subscription_group'];
    }
}
