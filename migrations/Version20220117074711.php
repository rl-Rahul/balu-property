<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220117074711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_payment (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, response VARCHAR(180) DEFAULT NULL, is_success TINYINT(1) NOT NULL, is_company TINYINT(1) DEFAULT \'0\' NOT NULL, transaction_id VARCHAR(255) DEFAULT NULL, amount DOUBLE PRECISION DEFAULT \'0\', period INT DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_234BA57DB5B48B91 (public_id), INDEX IDX_234BA57DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_property_payment (payment_id INT NOT NULL, property_id INT NOT NULL, INDEX IDX_7E96520C4C3A3BB (payment_id), INDEX IDX_7E96520C549213EC (property_id), PRIMARY KEY(payment_id, property_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_property (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, subscription_plan_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, address VARCHAR(255) NOT NULL, street_name VARCHAR(180) DEFAULT NULL, street_number VARCHAR(45) DEFAULT NULL, postal_code VARCHAR(45) DEFAULT NULL, city VARCHAR(180) DEFAULT NULL, state VARCHAR(45) DEFAULT NULL, country VARCHAR(45) DEFAULT NULL, country_code VARCHAR(20) DEFAULT NULL, currency VARCHAR(20) DEFAULT NULL, plan_start_date DATETIME DEFAULT NULL, plan_end_date DATETIME DEFAULT NULL, active TINYINT(1) DEFAULT \'0\', latitude VARCHAR(180) NOT NULL, longitude VARCHAR(180) DEFAULT NULL, recurring TINYINT(1) NOT NULL, pending_payment TINYINT(1) NOT NULL, stripe_subscription VARCHAR(255) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_DBB90EC3B5B48B91 (public_id), INDEX IDX_DBB90EC3A76ED395 (user_id), INDEX IDX_DBB90EC39B8CE200 (subscription_plan_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_property_attachments (property_id INT NOT NULL, document_id INT NOT NULL, INDEX IDX_888416D9549213EC (property_id), INDEX IDX_888416D9C33F7837 (document_id), PRIMARY KEY(property_id, document_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_property_document (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, title VARCHAR(255) NOT NULL, path VARCHAR(255) DEFAULT NULL, type VARCHAR(45) DEFAULT NULL, active TINYINT(1) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_AF7D0436B5B48B91 (public_id), INDEX IDX_AF7D0436A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_subscription_plan (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name VARCHAR(180) NOT NULL, period INT DEFAULT NULL, initial_plan TINYINT(1) DEFAULT NULL, appartment_max INT DEFAULT NULL, appartment_min INT DEFAULT NULL, amount DOUBLE PRECISION DEFAULT \'0\', active TINYINT(1) DEFAULT NULL, stripe_plan VARCHAR(255) DEFAULT NULL, inapp_plan DOUBLE PRECISION DEFAULT NULL, inapp_amount DOUBLE PRECISION DEFAULT \'0\', public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_C3F5F167B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_payment ADD CONSTRAINT FK_234BA57DA76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_property_payment ADD CONSTRAINT FK_7E96520C4C3A3BB FOREIGN KEY (payment_id) REFERENCES balu_payment (id)');
        $this->addSql('ALTER TABLE balu_property_payment ADD CONSTRAINT FK_7E96520C549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id)');
        $this->addSql('ALTER TABLE balu_property ADD CONSTRAINT FK_DBB90EC3A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_property ADD CONSTRAINT FK_DBB90EC39B8CE200 FOREIGN KEY (subscription_plan_id) REFERENCES balu_subscription_plan (id)');
        $this->addSql('ALTER TABLE balu_property_attachments ADD CONSTRAINT FK_888416D9549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id)');
        $this->addSql('ALTER TABLE balu_property_attachments ADD CONSTRAINT FK_888416D9C33F7837 FOREIGN KEY (document_id) REFERENCES balu_property_document (id)');
        $this->addSql('ALTER TABLE balu_property_document ADD CONSTRAINT FK_AF7D0436A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_property_payment DROP FOREIGN KEY FK_7E96520C4C3A3BB');
        $this->addSql('ALTER TABLE balu_property_payment DROP FOREIGN KEY FK_7E96520C549213EC');
        $this->addSql('ALTER TABLE balu_property_attachments DROP FOREIGN KEY FK_888416D9549213EC');
        $this->addSql('ALTER TABLE balu_property_attachments DROP FOREIGN KEY FK_888416D9C33F7837');
        $this->addSql('ALTER TABLE balu_property DROP FOREIGN KEY FK_DBB90EC39B8CE200');
        $this->addSql('DROP TABLE balu_payment');
        $this->addSql('DROP TABLE balu_property_payment');
        $this->addSql('DROP TABLE balu_property');
        $this->addSql('DROP TABLE balu_property_attachments');
        $this->addSql('DROP TABLE balu_property_document');
        $this->addSql('DROP TABLE balu_subscription_plan');
    }
}
