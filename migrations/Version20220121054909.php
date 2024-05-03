<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220121054909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_company_subscription_plan (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name VARCHAR(180) NOT NULL, period INT DEFAULT NULL, initial_plan TINYINT(1) DEFAULT NULL, amount DOUBLE PRECISION DEFAULT NULL, active TINYINT(1) DEFAULT NULL, stripe_plan VARCHAR(255) DEFAULT NULL, in_app_plan VARCHAR(250) DEFAULT NULL, in_app_amount DOUBLE PRECISION DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_E197ED17B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_user_subscription (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, company_subscription_plan_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, stripe_subscription VARCHAR(255) NOT NULL, is_recurring TINYINT(1) NOT NULL, is_free_plan_subscribed TINYINT(1) NOT NULL, is_expired TINYINT(1) NOT NULL, expiry_date VARCHAR(255) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_A99A2D5B5B48B91 (public_id), UNIQUE INDEX UNIQ_A99A2D5A76ED395 (user_id), UNIQUE INDEX UNIQ_A99A2D5C6BF9496 (company_subscription_plan_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_user_subscription ADD CONSTRAINT FK_A99A2D5A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_user_subscription ADD CONSTRAINT FK_A99A2D5C6BF9496 FOREIGN KEY (company_subscription_plan_id) REFERENCES balu_company_subscription_plan (id)');
        $this->addSql('ALTER TABLE balu_user ADD last_login DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_user_subscription DROP FOREIGN KEY FK_A99A2D5C6BF9496');
        $this->addSql('DROP TABLE balu_company_subscription_plan');
        $this->addSql('DROP TABLE balu_user_subscription');
        $this->addSql('ALTER TABLE balu_user DROP last_login');
    }
}
