<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220705125753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_apartment_log (id INT AUTO_INCREMENT NOT NULL, apartment_id INT DEFAULT NULL, object_type_id INT DEFAULT NULL, floor_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, reference_rate_id INT DEFAULT NULL, land_index_id INT DEFAULT NULL, contract_type_id INT DEFAULT NULL, net_rent_rate_currency_id INT DEFAULT NULL, additional_cost_currency_id INT DEFAULT NULL, mode_of_payment_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, area DOUBLE PRECISION DEFAULT NULL, room_count INT DEFAULT NULL, rent DOUBLE PRECISION DEFAULT NULL, sort_order INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, ceiling_height INT DEFAULT NULL, volume INT DEFAULT NULL, max_floor_loading INT DEFAULT NULL, official_number INT DEFAULT NULL, total_object_value DOUBLE PRECISION DEFAULT NULL, additional_cost_building DOUBLE PRECISION DEFAULT NULL, additional_cost_environment DOUBLE PRECISION DEFAULT NULL, additional_cost_heating DOUBLE PRECISION DEFAULT NULL, additional_cost_elevator DOUBLE PRECISION DEFAULT NULL, additional_cost_parking DOUBLE PRECISION DEFAULT NULL, additional_cost_renewal DOUBLE PRECISION DEFAULT NULL, additional_cost_maintenance DOUBLE PRECISION DEFAULT NULL, additional_cost_administration DOUBLE PRECISION DEFAULT NULL, additional_cost DOUBLE PRECISION DEFAULT NULL, net_rent_rate DOUBLE PRECISION DEFAULT NULL, base_index_date DATETIME DEFAULT NULL, base_index_value DOUBLE PRECISION DEFAULT NULL, active TINYINT(1) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_DD3AE955B5B48B91 (public_id), INDEX IDX_DD3AE955176DFE85 (apartment_id), INDEX IDX_DD3AE955C5020C33 (object_type_id), INDEX IDX_DD3AE955854679E2 (floor_id), INDEX IDX_DD3AE955B03A8386 (created_by_id), INDEX IDX_DD3AE955BBE93A37 (reference_rate_id), INDEX IDX_DD3AE955BE9E3FDB (land_index_id), INDEX IDX_DD3AE955CD1DF15B (contract_type_id), INDEX IDX_DD3AE955B646B3A (net_rent_rate_currency_id), INDEX IDX_DD3AE955BF390445 (additional_cost_currency_id), INDEX IDX_DD3AE955C9A9CD82 (mode_of_payment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_apartment_log ADD CONSTRAINT FK_DD3AE955176DFE85 FOREIGN KEY (apartment_id) REFERENCES balu_apartment (id)');
        $this->addSql('ALTER TABLE balu_apartment_log ADD CONSTRAINT FK_DD3AE955C5020C33 FOREIGN KEY (object_type_id) REFERENCES balu_object_types (id)');
        $this->addSql('ALTER TABLE balu_apartment_log ADD CONSTRAINT FK_DD3AE955854679E2 FOREIGN KEY (floor_id) REFERENCES balu_floor (id)');
        $this->addSql('ALTER TABLE balu_apartment_log ADD CONSTRAINT FK_DD3AE955B03A8386 FOREIGN KEY (created_by_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_apartment_log ADD CONSTRAINT FK_DD3AE955BBE93A37 FOREIGN KEY (reference_rate_id) REFERENCES balu_reference_index (id)');
        $this->addSql('ALTER TABLE balu_apartment_log ADD CONSTRAINT FK_DD3AE955BE9E3FDB FOREIGN KEY (land_index_id) REFERENCES balu_land_index (id)');
        $this->addSql('ALTER TABLE balu_apartment_log ADD CONSTRAINT FK_DD3AE955CD1DF15B FOREIGN KEY (contract_type_id) REFERENCES balu_contract_types (id)');
        $this->addSql('ALTER TABLE balu_apartment_log ADD CONSTRAINT FK_DD3AE955B646B3A FOREIGN KEY (net_rent_rate_currency_id) REFERENCES balu_currency (id)');
        $this->addSql('ALTER TABLE balu_apartment_log ADD CONSTRAINT FK_DD3AE955BF390445 FOREIGN KEY (additional_cost_currency_id) REFERENCES balu_currency (id)');
        $this->addSql('ALTER TABLE balu_apartment_log ADD CONSTRAINT FK_DD3AE955C9A9CD82 FOREIGN KEY (mode_of_payment_id) REFERENCES balu_mode_of_payment (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_apartment_log');
    }
}
