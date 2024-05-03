<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221017053420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    { 
        $this->addSql('ALTER TABLE balu_damage_image ADD display_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    { 
        $this->addSql('ALTER TABLE balu_damage_image DROP display_name');
    }
}
