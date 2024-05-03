<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220425123857 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment ADD folder_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_apartment ADD CONSTRAINT FK_2E284F9F162CB942 FOREIGN KEY (folder_id) REFERENCES balu_folder (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2E284F9F162CB942 ON balu_apartment (folder_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment DROP FOREIGN KEY FK_2E284F9F162CB942');
        $this->addSql('DROP INDEX UNIQ_2E284F9F162CB942 ON balu_apartment');
        $this->addSql('ALTER TABLE balu_apartment DROP folder_id');
    }
}
