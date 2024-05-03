<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230531120909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage_request ADD status_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_damage_request ADD CONSTRAINT FK_3AFB10726BF700BD FOREIGN KEY (status_id) REFERENCES balu_damage_status (id)');
        $this->addSql('CREATE INDEX IDX_3AFB10726BF700BD ON balu_damage_request (status_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage_request DROP FOREIGN KEY FK_3AFB10726BF700BD');
        $this->addSql('DROP INDEX IDX_3AFB10726BF700BD ON balu_damage_request');
        $this->addSql('ALTER TABLE balu_damage_request DROP status_id');
    }
}
