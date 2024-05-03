<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220829065956 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_feedback (id INT AUTO_INCREMENT NOT NULL, send_by_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, subject LONGTEXT DEFAULT NULL, message LONGTEXT DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_82625645B5B48B91 (public_id), INDEX IDX_82625645C3852542 (send_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_feedback ADD CONSTRAINT FK_82625645C3852542 FOREIGN KEY (send_by_id) REFERENCES balu_user_identity (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_feedback');
    }
}
