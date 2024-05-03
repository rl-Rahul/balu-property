<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220411063836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_object_contract_detail (id INT AUTO_INCREMENT NOT NULL, reference_rate_id INT DEFAULT NULL, land_index_id INT DEFAULT NULL, object_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, total_object_value DOUBLE PRECISION DEFAULT NULL, additional_cost_building DOUBLE PRECISION DEFAULT NULL, additional_cost_environment DOUBLE PRECISION DEFAULT NULL, additional_cost_heating DOUBLE PRECISION DEFAULT NULL, additional_cost_elevator DOUBLE PRECISION DEFAULT NULL, additional_cost_parking DOUBLE PRECISION DEFAULT NULL, additional_cost_renewal DOUBLE PRECISION DEFAULT NULL, additional_cost_maintenance DOUBLE PRECISION DEFAULT NULL, additional_cost_administration DOUBLE PRECISION DEFAULT NULL, mode_of_payment VARCHAR(25) DEFAULT NULL, additional_cost DOUBLE PRECISION DEFAULT NULL, net_rent_rate DOUBLE PRECISION DEFAULT NULL, net_rent_rate_currency VARCHAR(20) DEFAULT NULL, base_index_date DATETIME DEFAULT NULL, base_index_value DOUBLE PRECISION DEFAULT NULL, additional_cost_currency VARCHAR(50) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_39136DC0B5B48B91 (public_id), INDEX IDX_39136DC0BBE93A37 (reference_rate_id), INDEX IDX_39136DC0BE9E3FDB (land_index_id), INDEX IDX_39136DC0232D562B (object_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_object_contract_detail ADD CONSTRAINT FK_39136DC0BBE93A37 FOREIGN KEY (reference_rate_id) REFERENCES balu_reference_index (id)');
        $this->addSql('ALTER TABLE balu_object_contract_detail ADD CONSTRAINT FK_39136DC0BE9E3FDB FOREIGN KEY (land_index_id) REFERENCES balu_land_index (id)');
        $this->addSql('ALTER TABLE balu_object_contract_detail ADD CONSTRAINT FK_39136DC0232D562B FOREIGN KEY (object_id) REFERENCES balu_apartment (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_object_contract_detail');
    }
}
