<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220222115744 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_contract_type ADD object_id INT NOT NULL');
        $this->addSql('ALTER TABLE balu_contract_type ADD CONSTRAINT FK_40BFEA13232D562B FOREIGN KEY (object_id) REFERENCES balu_apartment (id)');
        $this->addSql('CREATE INDEX IDX_40BFEA13232D562B ON balu_contract_type (object_id)');
      
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_contract_type DROP FOREIGN KEY FK_40BFEA13232D562B');
        $this->addSql('DROP INDEX IDX_40BFEA13232D562B ON balu_contract_type');
        $this->addSql('ALTER TABLE balu_contract_type DROP object_id');
    }
}
