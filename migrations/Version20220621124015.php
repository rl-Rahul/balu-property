<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220621124015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage_image ADD folder_id INT NOT NULL, ADD file_size DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_damage_image ADD CONSTRAINT FK_968A7E21162CB942 FOREIGN KEY (folder_id) REFERENCES balu_folder (id)');
        $this->addSql('CREATE INDEX IDX_968A7E21162CB942 ON balu_damage_image (folder_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage_image DROP FOREIGN KEY FK_968A7E21162CB942');
        $this->addSql('DROP INDEX IDX_968A7E21162CB942 ON balu_damage_image');
        $this->addSql('ALTER TABLE balu_damage_image DROP folder_id, DROP file_size');
    }
}
