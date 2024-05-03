<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220516063133 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_currency (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name_en VARCHAR(30) DEFAULT NULL, name_de VARCHAR(30) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_391D9A22B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_mode_of_payment (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name_en VARCHAR(50) DEFAULT NULL, name_de VARCHAR(50) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_F9B9A84BB5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_object_contract_detail ADD net_rent_rate_currency_id INT DEFAULT NULL, ADD additional_cost_currency_id INT DEFAULT NULL, ADD mode_of_payment_id INT DEFAULT NULL, DROP mode_of_payment, DROP net_rent_rate_currency, DROP additional_cost_currency');
        $this->addSql('ALTER TABLE balu_object_contract_detail ADD CONSTRAINT FK_39136DC0B646B3A FOREIGN KEY (net_rent_rate_currency_id) REFERENCES balu_currency (id)');
        $this->addSql('ALTER TABLE balu_object_contract_detail ADD CONSTRAINT FK_39136DC0BF390445 FOREIGN KEY (additional_cost_currency_id) REFERENCES balu_currency (id)');
        $this->addSql('ALTER TABLE balu_object_contract_detail ADD CONSTRAINT FK_39136DC0C9A9CD82 FOREIGN KEY (mode_of_payment_id) REFERENCES balu_mode_of_payment (id)');
        $this->addSql('CREATE INDEX IDX_39136DC0B646B3A ON balu_object_contract_detail (net_rent_rate_currency_id)');
        $this->addSql('CREATE INDEX IDX_39136DC0BF390445 ON balu_object_contract_detail (additional_cost_currency_id)');
        $this->addSql('CREATE INDEX IDX_39136DC0C9A9CD82 ON balu_object_contract_detail (mode_of_payment_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_object_contract_detail DROP FOREIGN KEY FK_39136DC0B646B3A');
        $this->addSql('ALTER TABLE balu_object_contract_detail DROP FOREIGN KEY FK_39136DC0BF390445');
        $this->addSql('ALTER TABLE balu_object_contract_detail DROP FOREIGN KEY FK_39136DC0C9A9CD82');
        $this->addSql('DROP TABLE balu_currency');
        $this->addSql('DROP TABLE balu_mode_of_payment');
        $this->addSql('ALTER TABLE balu_apartment_document ADD file_size DOUBLE PRECISION DEFAULT NULL, ADD mime_type VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD file_path VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP title');
        $this->addSql('DROP INDEX IDX_39136DC0B646B3A ON balu_object_contract_detail');
        $this->addSql('DROP INDEX IDX_39136DC0BF390445 ON balu_object_contract_detail');
        $this->addSql('DROP INDEX IDX_39136DC0C9A9CD82 ON balu_object_contract_detail');
        $this->addSql('ALTER TABLE balu_object_contract_detail ADD mode_of_payment VARCHAR(25) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD net_rent_rate_currency VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD additional_cost_currency VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP net_rent_rate_currency_id, DROP additional_cost_currency_id, DROP mode_of_payment_id');
    }
}
