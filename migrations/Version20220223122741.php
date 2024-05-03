<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220223122741 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_contract_type DROP FOREIGN KEY FK_40BFEA13CED8D199');
        $this->addSql('ALTER TABLE balu_contract_type ADD CONSTRAINT FK_40BFEA13CED8D199 FOREIGN KEY (base_index_id) REFERENCES balu_land_index (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_contract_type DROP FOREIGN KEY FK_40BFEA13CED8D199');
        $this->addSql('ALTER TABLE balu_contract_type ADD CONSTRAINT FK_40BFEA13CED8D199 FOREIGN KEY (base_index_id) REFERENCES balu_reference_index (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
