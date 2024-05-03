<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\DamageStatus;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class DamageStatusFixture extends Fixture implements FixtureGroupInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getDamageStatus() as $key => $val) {
            $existingDamageStatus = $manager->getRepository(DamageStatus::class)->findOneBy(['key' => $key]);
            if (!$existingDamageStatus instanceof DamageStatus) {
                $damageStatus = new DamageStatus();
                $damageStatus->setKey($key);
                $damageStatus->setStatus($val['status']);
                $damageStatus->setCommentRequired($val['commentRequired']);
                $damageStatus->setDeleted(0);
                $manager->persist($damageStatus);
            }
        }

        $manager->flush();
    }

    /**
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['damage_status'];
    }


    /**
     * @return array
     */
    public static function getDamageStatus(): array
    {
        return [
            "TENANT_CREATE_DAMAGE" => [
                "status" => "Tenant Create Damage",
                "commentRequired" => 0
            ],
            "OWNER_CREATE_DAMAGE" => [
                "status" => "Owner Create Damage",
                "commentRequired" => 0
            ],
            "OBJECT_OWNER_CREATE_DAMAGE" => [
                "status" => "Object Owner Create Damage",
                "commentRequired" => 0
            ],
            "JANITOR_CREATE_DAMAGE" => [
                "status" => "Janitor Create Damage",
                "commentRequired" => 0
            ],
            "TENANT_SEND_TO_COMPANY_WITH_OFFER" => [
                "status" => "Tenant send to company With offer",
                "commentRequired" => 0
            ],
            "TENANT_SEND_TO_COMPANY_WITHOUT_OFFER" => [
                "status" => "Tenant send to company Without offer",
                "commentRequired" => 0
            ],
            "TENANT_REJECTS_THE_OFFER" => [
                "status" => "Tenant rejects the offer",
                "commentRequired" => 1
            ],
            "TENANT_REJECTS_DATE" => [
                "status" => "Tenant rejects date",
                "commentRequired" => 1
            ],
            "TENANT_CLOSE_THE_DAMAGE" => [
                "status" => "Tenant Close the damage",
                "commentRequired" => 0
            ],
            "TENANT_ACCEPTS_THE_OFFER" => [
                "status" => "Tenant accepts the offer",
                "commentRequired" => 0
            ],
            "TENANT_ACCEPTS_DATE" => [
                "status" => "Tenant accepts date",
                "commentRequired" => 0
            ],
            "REPAIR_CONFIRMED" => [
                "status" => "Repair Confirmed",
                "commentRequired" => 0
            ],
            "OWNER_SEND_TO_COMPANY_WITH_OFFER" => [
                "status" => "Owner Send to Company With offer",
                "commentRequired" => 0
            ],
            "OWNER_SEND_TO_COMPANY_WITHOUT_OFFER" => [
                "status" => "Owner Send to Company Without offer",
                "commentRequired" => 0
            ],
            "OWNER_REJECT_DAMAGE" => [
                "status" => "Owner Reject Damage",
                "commentRequired" => 1
            ],
            "PROPERTY_ADMIN_REJECT_DAMAGE" => [
                "status" => "Property Admin  Reject Damage",
                "commentRequired" => 1
            ],
            "OBJECT_OWNER_REJECT_DAMAGE" => [
                "status" => "Object Owner Reject Damage",
                "commentRequired" => 1
            ],
            "TENANT_REJECT_DAMAGE" => [
                "status" => "Tenant Reject Damage",
                "commentRequired" => 1
            ],
            "OWNER_REJECTS_THE_OFFER" => [
                "status" => "Owner rejects the offer",
                "commentRequired" => 1
            ],
            "OWNER_REJECTS_DATE" => [
                "status" => "Owner rejects date",
                "commentRequired" => 1
            ],
            "OWNER_CLOSE_THE_DAMAGE" => [
                "status" => "Owner Close The Damage",
                "commentRequired" => 0
            ],
            "OWNER_ACCEPTS_THE_OFFER" => [
                "status" => "Owner accepts the offer",
                "commentRequired" => 0
            ],
            "OWNER_ACCEPTS_DATE" => [
                "status" => "Owner accepts date",
                "commentRequired" => 0
            ],
            "DEFECT_RAISED" => [
                "status" => "Defect Raised",
                "commentRequired" => 0
            ],
            "COMPANY_SCHEDULE_DATE" => [
                "status" => "Company schedule date",
                "commentRequired" => 0
            ],
            "COMPANY_REJECT_THE_DAMAGE" => [
                "status" => "Company reject the damage",
                "commentRequired" => 1
            ],
            "COMPANY_GIVE_OFFER_TO_TENANT" => [
                "status" => "Company give offer to tenant",
                "commentRequired" => 0
            ],
            "COMPANY_GIVE_OFFER_TO_OWNER" => [
                "status" => "Company give offer to owner",
                "commentRequired" => 0
            ],
            "COMPANY_ACCEPTS_DAMAGE_WITH_OFFER" => [
                "status" => "Company Accepts Damage With Offer",
                "commentRequired" => 0
            ],
            "COMPANY_ACCEPTS_DAMAGE_WITHOUT_OFFER" => [
                "status" => "Company accepts damage without offer",
                "commentRequired" => 0
            ],
            "PROPERTY_ADMIN_SEND_TO_COMPANY_WITH_OFFER" => [
                "status" => "Property Admin send to Company With offer",
                "commentRequired" => 0
            ],
            "PROPERTY_ADMIN_SEND_TO_COMPANY_WITHOUT_OFFER" => [
                "status" => "Property Admin send to Company Without offer",
                "commentRequired" => 0
            ],
            "COMPANY_GIVE_OFFER_TO_PROPERTY_ADMIN" => [
                "status" => "Company give offer to Property Admin",
                "commentRequired" => 0
            ],
            "PROPERTY_ADMIN_ACCEPTS_THE_OFFER" => [
                "status" => "Property Admin accepts the offer",
                "commentRequired" => 0
            ],
            "PROPERTY_ADMIN_REJECTS_THE_OFFER" => [
                "status" => "Property Admin rejects the offer",
                "commentRequired" => 1
            ],
            "PROPERTY_ADMIN_ACCEPTS_DATE" => [
                "status" => "Property Admin accepts the date",
                "commentRequired" => 0
            ],
            "PROPERTY_ADMIN_REJECTS_DATE" => [
                "status" => "Property Admin rejects the date",
                "commentRequired" => 1
            ],
            "PROPERTY_ADMIN_CLOSE_THE_DAMAGE" => [
                "status" => "Property admin close the damage",
                "commentRequired" => 0
            ],
            "JANITOR_SEND_TO_COMPANY_WITH_OFFER" => [
                "status" => "Janitor send to Company With offer",
                "commentRequired" => 0
            ],
            "JANITOR_SEND_TO_COMPANY_WITHOUT_OFFER" => [
                "status" => "Janitor send to Company Without offer",
                "commentRequired" => 0
            ],
            "COMPANY_GIVE_OFFER_TO_JANITOR" => [
                "status" => "Company give offer to Janitor",
                "commentRequired" => 0
            ],
            "JANITOR_ACCEPTS_THE_OFFER" => [
                "status" => "Janitor accepts the offer",
                "commentRequired" => 0
            ],
            "JANITOR_REJECTS_THE_OFFER" => [
                "status" => "Janitor rejects the offer",
                "commentRequired" => 1
            ],
            "JANITOR_ACCEPTS_DATE" => [
                "status" => "Janitor accepts the date",
                "commentRequired" => 0
            ],
            "JANITOR_REJECTS_DATE" => [
                "status" => "Janitor rejects the date",
                "commentRequired" => 1
            ],
            "JANITOR_CLOSE_THE_DAMAGE" => [
                "status" => "Janitor close the damage",
                "commentRequired" => 0
            ],
            "OBJECT_OWNER_SEND_TO_COMPANY_WITH_OFFER" => [
                "status" => "Object Owner send to Company With offer",
                "commentRequired" => 0
            ],
            "OBJECT_OWNER_SEND_TO_COMPANY_WITHOUT_OFFER" => [
                "status" => "Object Owner send to Company Without offer",
                "commentRequired" => 0
            ],
            "COMPANY_GIVE_OFFER_TO_OBJECT_OWNER" => [
                "status" => "Company give offer to Object Owner",
                "commentRequired" => 0
            ],
            "OBJECT_OWNER_ACCEPTS_THE_OFFER" => [
                "status" => "Object Owner accepts the offer",
                "commentRequired" => 0
            ],
            "OBJECT_OWNER_REJECTS_THE_OFFER" => [
                "status" => "Object Owner rejects the offer",
                "commentRequired" => 1
            ],
            "OBJECT_OWNER_ACCEPTS_DATE" => [
                "status" => "Object Owner accepts the date",
                "commentRequired" => 0
            ],
            "OBJECT_OWNER_REJECTS_DATE" => [
                "status" => "Object Owner rejects the date",
                "commentRequired" => 1
            ],
            "OBJECT_OWNER_CLOSE_THE_DAMAGE" => [
                "status" => "Object Owner close the damage",
                "commentRequired" => 0
            ],
            "OWNER_ACCEPTS_DAMAGE" => [
                "status" => "Owner accepts the damage",
                "commentRequired" => 0
            ],
            "PROPERTY_ADMIN_ACCEPTS_DAMAGE" => [
                "status" => "Property admin accepts the damage",
                "commentRequired" => 0
            ],
            "PROPERTY_ADMIN_CREATE_DAMAGE" => [
                "status" => "Property admin create damage",
                "commentRequired" => 0
            ],
            "JANITOR_ACCEPTS_DAMAGE" => [
                "status" => "Janitor accepts damage",
                "commentRequired" => 0
            ],
            "JANITOR_REJECT_DAMAGE" => [
                "status" => "Janitor rejects damage",
                "commentRequired" => 1
            ],
        ];
    }
}
