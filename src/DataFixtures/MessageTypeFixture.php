<?php

namespace App\DataFixtures;

use App\Entity\MessageType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * Class MessageTypeFixture
 * @package App\DataFixtures
 */
class MessageTypeFixture extends Fixture implements FixtureGroupInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $types = ['ticket', 'question', 'information'];
        $typesEn = ['Ticket', 'Question', 'Information'];
        $typesDe = ['Ticket', 'Frage', 'Informationen'];
        foreach ($types as $key => $type) {
            $data = new MessageType();
            $data->setNameEn($typesEn[$key])
                    ->setNameDe($typesDe[$key])
                    ->setTypeKey($type);
            $manager->persist($data);
        }

        $manager->flush();
    }

    /**
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['message_group'];
    }
}
