<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220210092711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_property_group (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name VARCHAR(255) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_2E12A1E3B5B48B91 (public_id), INDEX IDX_2E12A1E3B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_property_group ADD CONSTRAINT FK_2E12A1E3B03A8386 FOREIGN KEY (created_by_id) REFERENCES balu_user_identity (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_property_group');
    }
}
