<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220412050700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET foreign_key_checks = 0');
        $this->addSql('CREATE TABLE balu_property_user (id INT AUTO_INCREMENT NOT NULL, property_id INT DEFAULT NULL, directory_id INT DEFAULT NULL, role_id INT DEFAULT NULL, object_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, is_active TINYINT(1) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_369F9A21B5B48B91 (public_id), INDEX IDX_369F9A21549213EC (property_id), INDEX IDX_369F9A212C94069F (directory_id), INDEX IDX_369F9A21D60322AC (role_id), INDEX IDX_369F9A21232D562B (object_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_property_user ADD CONSTRAINT FK_369F9A21549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id)');
        $this->addSql('ALTER TABLE balu_property_user ADD CONSTRAINT FK_369F9A212C94069F FOREIGN KEY (directory_id) REFERENCES balu_directory (id)');
        $this->addSql('ALTER TABLE balu_property_user ADD CONSTRAINT FK_369F9A21D60322AC FOREIGN KEY (role_id) REFERENCES balu_role (id)');
        $this->addSql('ALTER TABLE balu_property_user ADD CONSTRAINT FK_369F9A21232D562B FOREIGN KEY (object_id) REFERENCES balu_apartment (id)');
        $this->addSql('ALTER TABLE balu_property ADD administrator_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_property ADD CONSTRAINT FK_DBB90EC34B09E92C FOREIGN KEY (administrator_id) REFERENCES balu_user_identity (id)');
        $this->addSql('CREATE INDEX IDX_DBB90EC34B09E92C ON balu_property (administrator_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_property_user');
        $this->addSql('ALTER TABLE balu_property DROP FOREIGN KEY FK_DBB90EC34B09E92C');
        $this->addSql('DROP INDEX IDX_DBB90EC34B09E92C ON balu_property');
        $this->addSql('ALTER TABLE balu_property DROP administrator_id');
    }
}
