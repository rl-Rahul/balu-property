<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220926100857 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment DROP FOREIGN KEY FK_2E284F9F8FB58DBC');
        $this->addSql('ALTER TABLE balu_apartment_document_mapping DROP FOREIGN KEY FK_CE30D023C33F7837');
        $this->addSql('ALTER TABLE balu_document_link DROP FOREIGN KEY FK_350CE630C33F7837');
        $this->addSql('ALTER TABLE balu_property_attachments DROP FOREIGN KEY FK_888416D9C33F7837');
        $this->addSql('DROP TABLE balu_apartment_document');
        $this->addSql('DROP TABLE balu_apartment_document_mapping');
        $this->addSql('DROP TABLE balu_document_link');
        $this->addSql('DROP TABLE balu_property_attachments');
        $this->addSql('DROP TABLE balu_property_document');
        $this->addSql('DROP INDEX IDX_2E284F9F8FB58DBC ON balu_apartment');
        $this->addSql('ALTER TABLE balu_apartment DROP apartment_document_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_apartment_document (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name VARCHAR(45) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, path VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, active TINYINT(1) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, original_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, file_display_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, mime_type VARCHAR(45) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, is_public TINYINT(1) NOT NULL, file_path LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_D2CC8105A76ED395 (user_id), UNIQUE INDEX UNIQ_D2CC8105B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE balu_apartment_document_mapping (id INT AUTO_INCREMENT NOT NULL, apartment_id INT DEFAULT NULL, document_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_CE30D023176DFE85 (apartment_id), INDEX IDX_CE30D023C33F7837 (document_id), UNIQUE INDEX UNIQ_CE30D023B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE balu_document_link (id INT AUTO_INCREMENT NOT NULL, document_id INT DEFAULT NULL, user_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, document_path VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_350CE630A76ED395 (user_id), INDEX IDX_350CE630C33F7837 (document_id), UNIQUE INDEX UNIQ_350CE630B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE balu_property_attachments (property_id INT NOT NULL, document_id INT NOT NULL, INDEX IDX_888416D9549213EC (property_id), INDEX IDX_888416D9C33F7837 (document_id), PRIMARY KEY(property_id, document_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE balu_property_document (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, property_id INT DEFAULT NULL, folder_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, path LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, type VARCHAR(45) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, active TINYINT(1) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', file_display_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, original_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, file_path LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, mime_type VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, file_size DOUBLE PRECISION DEFAULT NULL, is_public TINYINT(1) NOT NULL, INDEX IDX_AF7D0436162CB942 (folder_id), INDEX IDX_AF7D0436549213EC (property_id), INDEX IDX_AF7D0436A76ED395 (user_id), UNIQUE INDEX UNIQ_AF7D0436B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE balu_apartment_document ADD CONSTRAINT FK_D2CC8105A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_apartment_document_mapping ADD CONSTRAINT FK_CE30D023176DFE85 FOREIGN KEY (apartment_id) REFERENCES balu_apartment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_apartment_document_mapping ADD CONSTRAINT FK_CE30D023C33F7837 FOREIGN KEY (document_id) REFERENCES balu_apartment_document (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_document_link ADD CONSTRAINT FK_350CE630A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_document_link ADD CONSTRAINT FK_350CE630C33F7837 FOREIGN KEY (document_id) REFERENCES balu_property_document (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_property_attachments ADD CONSTRAINT FK_888416D9549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_property_attachments ADD CONSTRAINT FK_888416D9C33F7837 FOREIGN KEY (document_id) REFERENCES balu_property_document (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_property_document ADD CONSTRAINT FK_AF7D0436162CB942 FOREIGN KEY (folder_id) REFERENCES balu_folder (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_property_document ADD CONSTRAINT FK_AF7D0436549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_property_document ADD CONSTRAINT FK_AF7D0436A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_apartment ADD apartment_document_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_apartment ADD CONSTRAINT FK_2E284F9F8FB58DBC FOREIGN KEY (apartment_document_id) REFERENCES balu_apartment_document (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_2E284F9F8FB58DBC ON balu_apartment (apartment_document_id)');
    }
}
