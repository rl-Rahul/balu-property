<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220503110520 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_contract_type_ownership');
        $this->addSql('DROP TABLE balu_contract_type_rental');
        $this->addSql('ALTER TABLE balu_object_contracts DROP INDEX UNIQ_9C0A8D16AA567C, ADD INDEX IDX_9C0A8D16AA567C (rental_type_id)');
        $this->addSql('ALTER TABLE balu_object_contracts DROP INDEX UNIQ_9C0A8DA5B96DD8, ADD INDEX IDX_9C0A8DA5B96DD8 (notice_period_id)');
        $this->addSql('ALTER TABLE balu_object_contracts CHANGE notice_period_id notice_period_id INT NOT NULL, CHANGE rental_type_id rental_type_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_contract_type_ownership (id INT AUTO_INCREMENT NOT NULL, object_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, total_object_value DOUBLE PRECISION DEFAULT NULL, additional_cost_building DOUBLE PRECISION DEFAULT NULL, additional_cost_environment DOUBLE PRECISION DEFAULT NULL, additional_cost_heating DOUBLE PRECISION DEFAULT NULL, additional_cost_elevator DOUBLE PRECISION DEFAULT NULL, additional_cost_parking DOUBLE PRECISION DEFAULT NULL, additional_cost_renewal DOUBLE PRECISION DEFAULT NULL, additional_cost_maintenance DOUBLE PRECISION DEFAULT NULL, additional_cost_administration DOUBLE PRECISION DEFAULT NULL, mode_of_payment VARCHAR(25) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', additional_cost DOUBLE PRECISION DEFAULT NULL, INDEX IDX_7D50CD65232D562B (object_id), UNIQUE INDEX UNIQ_7D50CD65B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE balu_contract_type_rental (id INT AUTO_INCREMENT NOT NULL, reference_rate_id INT DEFAULT NULL, land_index_id INT DEFAULT NULL, object_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, net_rent_rate DOUBLE PRECISION DEFAULT NULL, net_rent_rate_currency VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, additional_cost VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, mode_of_payment VARCHAR(25) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, base_index_date DATETIME DEFAULT NULL, base_index_value DOUBLE PRECISION DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', additional_cost_currency VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_7405CB1232D562B (object_id), INDEX IDX_7405CB1BBE93A37 (reference_rate_id), INDEX IDX_7405CB1BE9E3FDB (land_index_id), UNIQUE INDEX UNIQ_7405CB1B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE balu_contract_type_ownership ADD CONSTRAINT FK_7D50CD65232D562B FOREIGN KEY (object_id) REFERENCES balu_apartment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_contract_type_rental ADD CONSTRAINT FK_7405CB1232D562B FOREIGN KEY (object_id) REFERENCES balu_apartment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_contract_type_rental ADD CONSTRAINT FK_7405CB1BBE93A37 FOREIGN KEY (reference_rate_id) REFERENCES balu_reference_index (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_contract_type_rental ADD CONSTRAINT FK_7405CB1BE9E3FDB FOREIGN KEY (land_index_id) REFERENCES balu_land_index (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_object_contracts DROP INDEX IDX_9C0A8DA5B96DD8, ADD UNIQUE INDEX UNIQ_9C0A8DA5B96DD8 (notice_period_id)');
        $this->addSql('ALTER TABLE balu_object_contracts DROP INDEX IDX_9C0A8D16AA567C, ADD UNIQUE INDEX UNIQ_9C0A8D16AA567C (rental_type_id)');
        $this->addSql('ALTER TABLE balu_object_contracts CHANGE notice_period_id notice_period_id INT DEFAULT NULL, CHANGE rental_type_id rental_type_id INT DEFAULT NULL');
    }
}
