<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220329103946 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_land_index DROP FOREIGN KEY FK_6E8DA431CD1DF15B');
        $this->addSql('DROP INDEX IDX_6E8DA431CD1DF15B ON balu_land_index');
        $this->addSql('ALTER TABLE balu_land_index DROP contract_type_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_land_index ADD contract_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_land_index ADD CONSTRAINT FK_6E8DA431CD1DF15B FOREIGN KEY (contract_type_id) REFERENCES balu_contract_type_rental (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_6E8DA431CD1DF15B ON balu_land_index (contract_type_id)');
    }
}
