<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220418063055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_object_contract_detail ADD contract_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_object_contract_detail ADD CONSTRAINT FK_39136DC0CD1DF15B FOREIGN KEY (contract_type_id) REFERENCES balu_contract_types (id)');
        $this->addSql('CREATE INDEX IDX_39136DC0CD1DF15B ON balu_object_contract_detail (contract_type_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_object_contract_detail DROP FOREIGN KEY FK_39136DC0CD1DF15B');
        $this->addSql('DROP INDEX IDX_39136DC0CD1DF15B ON balu_object_contract_detail');
        $this->addSql('ALTER TABLE balu_object_contract_detail DROP contract_type_id');
    }
}
