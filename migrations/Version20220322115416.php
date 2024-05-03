<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220322115416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_contract_type_ownership (id INT AUTO_INCREMENT NOT NULL, object_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, total_object_value DOUBLE PRECISION DEFAULT NULL, additional_cost_building DOUBLE PRECISION DEFAULT NULL, additional_cost_environment DOUBLE PRECISION DEFAULT NULL, additional_cost_heating DOUBLE PRECISION DEFAULT NULL, additional_cost_elevator DOUBLE PRECISION DEFAULT NULL, additional_cost_parking DOUBLE PRECISION DEFAULT NULL, additional_cost_renewal DOUBLE PRECISION DEFAULT NULL, additional_cost_maintenance DOUBLE PRECISION DEFAULT NULL, additional_cost_administration DOUBLE PRECISION DEFAULT NULL, mode_of_payment VARCHAR(25) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_7D50CD65B5B48B91 (public_id), INDEX IDX_7D50CD65232D562B (object_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_contract_type_rental (id INT AUTO_INCREMENT NOT NULL, reference_rate_id INT DEFAULT NULL, land_index_id INT DEFAULT NULL, oobject_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, net_rent_rate DOUBLE PRECISION DEFAULT NULL, net_rent_rate_currency VARCHAR(20) DEFAULT NULL, additional_cost VARCHAR(255) DEFAULT NULL, mode_of_payment VARCHAR(25) DEFAULT NULL, base_index_date DATETIME DEFAULT NULL, base_index_value DOUBLE PRECISION DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_7405CB1B5B48B91 (public_id), INDEX IDX_7405CB1BBE93A37 (reference_rate_id), INDEX IDX_7405CB1BE9E3FDB (land_index_id), UNIQUE INDEX UNIQ_7405CB1642750E (oobject_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_contract_type_ownership ADD CONSTRAINT FK_7D50CD65232D562B FOREIGN KEY (object_id) REFERENCES balu_apartment (id)');
        $this->addSql('ALTER TABLE balu_contract_type_rental ADD CONSTRAINT FK_7405CB1BBE93A37 FOREIGN KEY (reference_rate_id) REFERENCES balu_reference_index (id)');
        $this->addSql('ALTER TABLE balu_contract_type_rental ADD CONSTRAINT FK_7405CB1BE9E3FDB FOREIGN KEY (land_index_id) REFERENCES balu_land_index (id)');
        $this->addSql('ALTER TABLE balu_contract_type_rental ADD CONSTRAINT FK_7405CB1642750E FOREIGN KEY (oobject_id) REFERENCES balu_apartment (id)');
        $this->addSql('ALTER TABLE balu_land_index DROP FOREIGN KEY FK_6E8DA431CD1DF15B');
        $this->addSql('DROP TABLE balu_contract_type');
        $this->addSql('ALTER TABLE balu_apartment ADD contract_type_id INT NOT NULL');
        $this->addSql('ALTER TABLE balu_apartment ADD CONSTRAINT FK_2E284F9FCD1DF15B FOREIGN KEY (contract_type_id) REFERENCES balu_contract_types (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2E284F9FCD1DF15B ON balu_apartment (contract_type_id)');
        $this->addSql('ALTER TABLE balu_land_index ADD CONSTRAINT FK_6E8DA431CD1DF15B FOREIGN KEY (contract_type_id) REFERENCES balu_contract_type_rental (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_land_index DROP FOREIGN KEY FK_6E8DA431CD1DF15B');
        $this->addSql('CREATE TABLE balu_contract_type (id INT AUTO_INCREMENT NOT NULL, reference_rate_id INT DEFAULT NULL, object_id INT NOT NULL, land_index_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, type SMALLINT NOT NULL, net_rent_rate DOUBLE PRECISION NOT NULL, net_rent_rate_currency VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, additional_cost VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, mode_of_payment VARCHAR(25) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, base_index_date DATETIME DEFAULT NULL, base_index_value DOUBLE PRECISION DEFAULT NULL, total_object_value DOUBLE PRECISION DEFAULT NULL, additional_cost_building DOUBLE PRECISION DEFAULT NULL, additional_cost_environment DOUBLE PRECISION DEFAULT NULL, additional_cost_heating DOUBLE PRECISION DEFAULT NULL, additional_cost_elevator DOUBLE PRECISION DEFAULT NULL, additional_cost_parking DOUBLE PRECISION DEFAULT NULL, additional_cost_renewal DOUBLE PRECISION DEFAULT NULL, additional_cost_maintenance DOUBLE PRECISION DEFAULT NULL, additional_cost_administration DOUBLE PRECISION DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_40BFEA13232D562B (object_id), INDEX IDX_40BFEA13BBE93A37 (reference_rate_id), INDEX IDX_40BFEA13BE9E3FDB (land_index_id), UNIQUE INDEX UNIQ_40BFEA13B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE balu_contract_type ADD CONSTRAINT FK_40BFEA13232D562B FOREIGN KEY (object_id) REFERENCES balu_apartment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_contract_type ADD CONSTRAINT FK_40BFEA13BBE93A37 FOREIGN KEY (reference_rate_id) REFERENCES balu_reference_index (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_contract_type ADD CONSTRAINT FK_40BFEA13BE9E3FDB FOREIGN KEY (land_index_id) REFERENCES balu_land_index (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP TABLE balu_contract_type_ownership');
        $this->addSql('DROP TABLE balu_contract_type_rental');
        $this->addSql('ALTER TABLE balu_apartment DROP FOREIGN KEY FK_2E284F9FCD1DF15B');
        $this->addSql('DROP INDEX UNIQ_2E284F9FCD1DF15B ON balu_apartment');
        $this->addSql('ALTER TABLE balu_apartment DROP contract_type_id');
        $this->addSql('ALTER TABLE balu_land_index DROP FOREIGN KEY FK_6E8DA431CD1DF15B');
        $this->addSql('ALTER TABLE balu_land_index ADD CONSTRAINT FK_6E8DA431CD1DF15B FOREIGN KEY (contract_type_id) REFERENCES balu_contract_type (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
