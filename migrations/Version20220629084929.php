<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220629084929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_object_contracts_log_user (id INT AUTO_INCREMENT NOT NULL, log_id INT DEFAULT NULL, user_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_9C8F9AC4B5B48B91 (public_id), INDEX IDX_9C8F9AC4EA675D86 (log_id), INDEX IDX_9C8F9AC4A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_object_contracts_document ADD CONSTRAINT FK_D81B288EA76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
