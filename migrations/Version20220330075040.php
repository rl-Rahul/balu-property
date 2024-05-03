<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220330075040 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }  

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_property ADD folder_id INT NOT NULL');
        $this->addSql('ALTER TABLE balu_property ADD CONSTRAINT FK_DBB90EC3162CB942 FOREIGN KEY (folder_id) REFERENCES balu_folder (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DBB90EC3162CB942 ON balu_property (folder_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_property DROP FOREIGN KEY FK_DBB90EC3162CB942');
        $this->addSql('DROP INDEX UNIQ_DBB90EC3162CB942 ON balu_property');
        $this->addSql('ALTER TABLE balu_property DROP folder_id');
    }
}
