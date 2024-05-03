<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230803091326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_folder ADD created_role_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_folder ADD CONSTRAINT FK_8B002D2F906930EA FOREIGN KEY (created_role_id) REFERENCES balu_role (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_folder DROP FOREIGN KEY FK_8B002D2F906930EA');
        $this->addSql('DROP INDEX IDX_8B002D2F906930EA ON balu_folder');
        $this->addSql('ALTER TABLE balu_folder DROP created_role_id');
    }
}
