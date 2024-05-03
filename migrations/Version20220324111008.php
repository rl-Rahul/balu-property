<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220324111008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment DROP INDEX UNIQ_2E284F9FCD1DF15B, ADD INDEX IDX_2E284F9FCD1DF15B (contract_type_id)');
        $this->addSql('ALTER TABLE balu_apartment CHANGE contract_type_id contract_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_contract_type_ownership CHANGE object_id object_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_contract_type_rental DROP INDEX UNIQ_7405CB1232D562B, ADD INDEX IDX_7405CB1232D562B (object_id)');
        $this->addSql('ALTER TABLE balu_contract_type_rental CHANGE object_id object_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment DROP INDEX IDX_2E284F9FCD1DF15B, ADD UNIQUE INDEX UNIQ_2E284F9FCD1DF15B (contract_type_id)');
        $this->addSql('ALTER TABLE balu_apartment CHANGE contract_type_id contract_type_id INT NOT NULL');
        $this->addSql('ALTER TABLE balu_contract_type_ownership CHANGE object_id object_id INT NOT NULL');
        $this->addSql('ALTER TABLE balu_contract_type_rental DROP INDEX IDX_7405CB1232D562B, ADD UNIQUE INDEX UNIQ_7405CB1232D562B (object_id)');
        $this->addSql('ALTER TABLE balu_contract_type_rental CHANGE object_id object_id INT NOT NULL');
    }
}
