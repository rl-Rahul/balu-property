<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220504122619 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_property_user ADD contract_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_property_user ADD CONSTRAINT FK_369F9A212576E0FD FOREIGN KEY (contract_id) REFERENCES balu_object_contracts (id)');
        $this->addSql('CREATE INDEX IDX_369F9A212576E0FD ON balu_property_user (contract_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_property_user DROP FOREIGN KEY FK_369F9A212576E0FD');
        $this->addSql('DROP INDEX IDX_369F9A212576E0FD ON balu_property_user');
        $this->addSql('ALTER TABLE balu_property_user DROP contract_id');
    }
}
