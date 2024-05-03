<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220622091649 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage ADD folder_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_damage ADD CONSTRAINT FK_766A708E162CB942 FOREIGN KEY (folder_id) REFERENCES balu_folder (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_766A708E162CB942 ON balu_damage (folder_id)');
        $this->addSql('ALTER TABLE balu_damage_image CHANGE path path VARCHAR(300) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage DROP FOREIGN KEY FK_766A708E162CB942');
        $this->addSql('DROP INDEX UNIQ_766A708E162CB942 ON balu_damage');
        $this->addSql('ALTER TABLE balu_damage DROP folder_id');
        $this->addSql('ALTER TABLE balu_damage_image CHANGE path path VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
