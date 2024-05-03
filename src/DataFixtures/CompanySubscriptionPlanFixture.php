<?php

namespace App\DataFixtures;

use App\Entity\CompanySubscriptionPlan;
use App\Entity\SubscriptionPlan;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * Class CompanySubscriptionPlanFixture
 * @package App\DataFixtures
 */
class CompanySubscriptionPlanFixture extends Fixture implements FixtureGroupInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $plan = new CompanySubscriptionPlan();
        $plan->setName('FREE PLAN')
            ->setNameDe('KOSTENLOSER Plan')
            ->setPeriod(30)
            ->setMaxPerson(1)
            ->setMinPerson(0)
            ->setAmount(20)
            ->setActive(1)
            ->setInitialPlan(1)
            ->setStripePlan()
            ->setColorCode('#363636')
            ->setTextColor('#FFFFFF')
            ->setIsPlatformUsageAllowed(false)
            ->setDetails(['description' => 'description', 'features' => ['features', 'trialLimit', 'noOfUsers', 'cancellation']]);
        
        $manager->persist($plan);
        
        $plan = new CompanySubscriptionPlan();
        $plan->setName('Company Subscription Plan Tier 1')
            ->setNameDe('Unternehmensabonnementplan Stufe 1')
            ->setPeriod(30)
            ->setMaxPerson(1)
            ->setMinPerson(0)
            ->setAmount(20)
            ->setActive(1)
            ->setInitialPlan(0)
            ->setStripePlan('price_1MwNlsFcdDSBW4LV3YKBUfPi')
            ->setColorCode('#363636')
            ->setTextColor('#000000')
            ->setIsPlatformUsageAllowed(true)
            ->setDetails(['description' => 'description', 'features' => ['features', 'noOfUsers', 'contractPeriod', 'cancellation']]);
        
        $manager->persist($plan);

        $plan = new CompanySubscriptionPlan();
        $plan->setName('Company Subscription Plan Tier 2')
            ->setNameDe('Unternehmensabonnementplan Stufe 2')
            ->setPeriod(30)
            ->setMaxPerson(4)
            ->setMinPerson(0)
            ->setAmount(50)
            ->setActive(1)
            ->setInitialPlan(0)
            ->setStripePlan('price_1MwNmEFcdDSBW4LVPNFKiLdD')
            ->setColorCode('#5595c2')
            ->setTextColor('#FFFFFF')
            ->setIsPlatformUsageAllowed(true)
            ->setDetails(['description' => 'description', 'features' => ['features', 'noOfUsers', 'contractPeriod', 'cancellation']]);
        $manager->persist($plan);

        $plan = new CompanySubscriptionPlan();
        $plan->setName('Company Subscription Plan Tier 3')
            ->setNameDe('Unternehmensabonnementplan Stufe 3')
            ->setPeriod(30)
            ->setMaxPerson(15)
            ->setMinPerson(0)
            ->setAmount(100)
            ->setActive(1)
            ->setInitialPlan(0)
            ->setStripePlan('price_1MwNmWFcdDSBW4LVUEZjNxap')
            ->setColorCode('#caa42b')
            ->setTextColor('#000000')
            ->setIsPlatformUsageAllowed(true)
            ->setDetails(['description' => 'description', 'features' => ['noOfUsers', 'features', 'contractPeriod', 'cancellation']]);
        $manager->persist($plan);

        $plan = new CompanySubscriptionPlan();
        $plan->setName('Company Subscription Plan Tier 4')
            ->setNameDe('Unternehmensabonnementplan Stufe 4')
            ->setPeriod(30)
            ->setMaxPerson(100)
            ->setMinPerson(0)
            ->setAmount(250)
            ->setActive(1)
            ->setInitialPlan(0)
            ->setStripePlan('price_1NcQ0DFcdDSBW4LVhOGumdgn')
            ->setColorCode('#caa42b')
            ->setTextColor('#FFFFFF')
            ->setIsPlatformUsageAllowed(true)
            ->setDetails(['description' => 'description', 'features' => ['noOfUsers', 'features', 'contractPeriod', 'cancellation']]);
        $manager->persist($plan);

        $manager->flush();
    }

    /**
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['company_subscription_group'];
    }
}
