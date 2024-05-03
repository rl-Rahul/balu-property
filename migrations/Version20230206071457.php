<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230206071457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_subscription_history (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, property_id INT DEFAULT NULL, company_plan_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, event VARCHAR(255) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_248ABCE9B5B48B91 (public_id), INDEX IDX_248ABCE9A76ED395 (user_id), INDEX IDX_248ABCE9549213EC (property_id), INDEX IDX_248ABCE9C8CB184C (company_plan_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_subscription_history ADD CONSTRAINT FK_248ABCE9A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_subscription_history ADD CONSTRAINT FK_248ABCE9549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id)');
        $this->addSql('ALTER TABLE balu_subscription_history ADD CONSTRAINT FK_248ABCE9C8CB184C FOREIGN KEY (company_plan_id) REFERENCES balu_company_subscription_plan (id)');
        
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_subscription_history DROP FOREIGN KEY FK_248ABCE9A76ED395');
        $this->addSql('ALTER TABLE balu_subscription_history DROP FOREIGN KEY FK_248ABCE9549213EC');
        $this->addSql('ALTER TABLE balu_subscription_history DROP FOREIGN KEY FK_248ABCE9C8CB184C');
        $this->addSql('DROP TABLE balu_subscription_history');
    }
}
