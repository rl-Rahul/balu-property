<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220224044603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_contract_type DROP FOREIGN KEY FK_40BFEA13CED8D199');
        $this->addSql('DROP INDEX IDX_40BFEA13CED8D199 ON balu_contract_type');
        $this->addSql('ALTER TABLE balu_contract_type DROP base_index_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_contract_type ADD base_index_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_contract_type ADD CONSTRAINT FK_40BFEA13CED8D199 FOREIGN KEY (base_index_id) REFERENCES balu_land_index (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_40BFEA13CED8D199 ON balu_contract_type (base_index_id)');
    }
}
