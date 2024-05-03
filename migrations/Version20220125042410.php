<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220125042410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_folder (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name VARCHAR(255) DEFAULT NULL, path VARCHAR(255) NOT NULL, accessability VARCHAR(255) DEFAULT NULL, display_name VARCHAR(255) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_8B002D2FB5B48B91 (public_id), INDEX IDX_8B002D2FB03A8386 (created_by_id), INDEX IDX_8B002D2F727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_folder ADD CONSTRAINT FK_8B002D2FB03A8386 FOREIGN KEY (created_by_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_folder ADD CONSTRAINT FK_8B002D2F727ACA70 FOREIGN KEY (parent_id) REFERENCES balu_folder (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_folder DROP FOREIGN KEY FK_8B002D2F727ACA70');
        $this->addSql('DROP TABLE balu_folder');
    }
}
