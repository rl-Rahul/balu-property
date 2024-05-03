<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230605112526 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage_offer ADD price_split LONGTEXT DEFAULT NULL AFTER amount');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage_offer DROP price_split');
    }
}
