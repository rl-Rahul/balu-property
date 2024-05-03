<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220412045502 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS balu_invitation_detail');
        $this->addSql('CREATE TABLE balu_directory (id INT AUTO_INCREMENT NOT NULL, invitor_id INT DEFAULT NULL, user_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_252E6311B5B48B91 (public_id), INDEX IDX_252E6311FD2F57A5 (invitor_id), INDEX IDX_252E6311A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_directory ADD CONSTRAINT FK_252E6311FD2F57A5 FOREIGN KEY (invitor_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_directory ADD CONSTRAINT FK_252E6311A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_directory');
    }
}
