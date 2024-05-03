<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220607072602 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_document (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, property_id INT DEFAULT NULL, apartment_id INT DEFAULT NULL, folder_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, title VARCHAR(255) NOT NULL, path VARCHAR(255) DEFAULT NULL, type VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, original_name VARCHAR(255) NOT NULL, display_name VARCHAR(255) NOT NULL, stored_path LONGTEXT NOT NULL, is_public TINYINT(1) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_8822986BB5B48B91 (public_id), INDEX IDX_8822986BA76ED395 (user_id), INDEX IDX_8822986B549213EC (property_id), INDEX IDX_8822986B176DFE85 (apartment_id), INDEX IDX_8822986B162CB942 (folder_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_document ADD CONSTRAINT FK_8822986BA76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_document ADD CONSTRAINT FK_8822986B549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id)');
        $this->addSql('ALTER TABLE balu_document ADD CONSTRAINT FK_8822986B176DFE85 FOREIGN KEY (apartment_id) REFERENCES balu_apartment (id)');
        $this->addSql('ALTER TABLE balu_document ADD CONSTRAINT FK_8822986B162CB942 FOREIGN KEY (folder_id) REFERENCES balu_folder (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_document');
    }
}
