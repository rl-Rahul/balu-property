<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

 
final class Version20220718075442 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_message_document (id INT AUTO_INCREMENT NOT NULL, message_id INT NOT NULL, folder_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name VARCHAR(255) NOT NULL, display_name VARCHAR(255) NOT NULL, path VARCHAR(255) NOT NULL, mime_type VARCHAR(255) NOT NULL, size DOUBLE PRECISION NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_9B3CD21EB5B48B91 (public_id), INDEX IDX_9B3CD21E537A1329 (message_id), INDEX IDX_9B3CD21E162CB942 (folder_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_message_document ADD CONSTRAINT FK_9B3CD21E537A1329 FOREIGN KEY (message_id) REFERENCES balu_message (id)');
        $this->addSql('ALTER TABLE balu_message_document ADD CONSTRAINT FK_9B3CD21E162CB942 FOREIGN KEY (folder_id) REFERENCES balu_folder (id)');
        $this->addSql('ALTER TABLE balu_message ADD folder_id INT DEFAULT NULL, ADD message LONGTEXT DEFAULT NULL, ADD subject VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_message ADD CONSTRAINT FK_F8DE110F162CB942 FOREIGN KEY (folder_id) REFERENCES balu_folder (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F8DE110F162CB942 ON balu_message (folder_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_message_document');
        $this->addSql('ALTER TABLE balu_message DROP FOREIGN KEY FK_F8DE110F162CB942');
        $this->addSql('DROP INDEX UNIQ_F8DE110F162CB942 ON balu_message');
        $this->addSql('ALTER TABLE balu_message DROP folder_id, DROP message, DROP subject');
    }
}
