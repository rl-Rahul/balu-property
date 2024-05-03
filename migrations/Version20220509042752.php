<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220509042752 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_object_contracts ADD folder_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_object_contracts ADD CONSTRAINT FK_9C0A8DF1595FC8 FOREIGN KEY (folder_id) REFERENCES balu_folder (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C0A8DF1595FC8 ON balu_object_contracts (folder_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_object_contracts DROP FOREIGN KEY FK_9C0A8DF1595FC8');
        $this->addSql('DROP INDEX UNIQ_9C0A8DF1595FC8 ON balu_object_contracts');
        $this->addSql('ALTER TABLE balu_object_contracts DROP folder_id');
    }
}
