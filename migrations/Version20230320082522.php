<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230320082522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_payment_log (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, url VARCHAR(255) NOT NULL, amount VARCHAR(255) NOT NULL, is_expired TINYINT(1) NOT NULL, payment_id VARCHAR(255) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_35587149B5B48B91 (public_id), INDEX IDX_35587149A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_payment_log ADD CONSTRAINT FK_35587149A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_payment_log');
    }
}
