<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220222101714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_contract_type (id INT AUTO_INCREMENT NOT NULL, reference_rate_id INT DEFAULT NULL, base_index_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, type SMALLINT NOT NULL, net_rent_rate DOUBLE PRECISION NOT NULL, net_rent_rate_currency VARCHAR(20) DEFAULT NULL, additional_cost VARCHAR(255) DEFAULT NULL, mode_of_payment VARCHAR(25) NOT NULL, base_index_date DATETIME DEFAULT NULL, base_index_value DOUBLE PRECISION DEFAULT NULL, total_object_value DOUBLE PRECISION NOT NULL, additional_cost_building DOUBLE PRECISION DEFAULT NULL, additional_cost_environment DOUBLE PRECISION DEFAULT NULL, additional_cost_heating DOUBLE PRECISION DEFAULT NULL, additional_cost_elevator DOUBLE PRECISION DEFAULT NULL, additional_cost_parking DOUBLE PRECISION DEFAULT NULL, additional_cost_renewal DOUBLE PRECISION DEFAULT NULL, additional_cost_maintenance DOUBLE PRECISION DEFAULT NULL, additional_cost_administration DOUBLE PRECISION DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_40BFEA13B5B48B91 (public_id), INDEX IDX_40BFEA13BBE93A37 (reference_rate_id), INDEX IDX_40BFEA13CED8D199 (base_index_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_contract_type ADD CONSTRAINT FK_40BFEA13BBE93A37 FOREIGN KEY (reference_rate_id) REFERENCES balu_reference_index (id)');
        $this->addSql('ALTER TABLE balu_contract_type ADD CONSTRAINT FK_40BFEA13CED8D199 FOREIGN KEY (base_index_id) REFERENCES balu_reference_index (id)');
        $this->addSql('ALTER TABLE balu_land_index ADD contract_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_land_index ADD CONSTRAINT FK_6E8DA431CD1DF15B FOREIGN KEY (contract_type_id) REFERENCES balu_contract_type (id)');
        $this->addSql('CREATE INDEX IDX_6E8DA431CD1DF15B ON balu_land_index (contract_type_id)');
       
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_land_index DROP FOREIGN KEY FK_6E8DA431CD1DF15B');
        $this->addSql('DROP TABLE balu_contract_type');
        $this->addSql('DROP INDEX IDX_6E8DA431CD1DF15B ON balu_land_index');
        $this->addSql('ALTER TABLE balu_land_index DROP contract_type_id');
    }
}
