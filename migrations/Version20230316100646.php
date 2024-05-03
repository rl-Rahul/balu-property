<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230316100646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_reset_object (id INT AUTO_INCREMENT NOT NULL, apartment_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, reason LONGTEXT DEFAULT NULL, is_super_admin_approved TINYINT(1) NOT NULL, super_admin_comment LONGTEXT NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_B8722A13B5B48B91 (public_id), INDEX IDX_B8722A13176DFE85 (apartment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_reset_object ADD CONSTRAINT FK_B8722A13176DFE85 FOREIGN KEY (apartment_id) REFERENCES balu_apartment (id)');
        $this->addSql('ALTER TABLE balu_property ADD is_cancelled_subscription TINYINT(1) NOT NULL, ADD cancelled_date DATETIME DEFAULT NULL, ADD reset_count VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_reset_object');
        $this->addSql('ALTER TABLE balu_property DROP is_cancelled_subscription, DROP cancelled_date, DROP reset_count');
    }
}
