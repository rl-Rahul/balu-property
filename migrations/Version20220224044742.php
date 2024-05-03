<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220224044742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_contract_type ADD land_index_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_contract_type ADD CONSTRAINT FK_40BFEA13BE9E3FDB FOREIGN KEY (land_index_id) REFERENCES balu_land_index (id)');
        $this->addSql('CREATE INDEX IDX_40BFEA13BE9E3FDB ON balu_contract_type (land_index_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_contract_type DROP FOREIGN KEY FK_40BFEA13BE9E3FDB');
        $this->addSql('DROP INDEX IDX_40BFEA13BE9E3FDB ON balu_contract_type');
        $this->addSql('ALTER TABLE balu_contract_type DROP land_index_id');
    }
}
