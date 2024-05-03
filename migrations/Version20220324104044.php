<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220324104044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_contract_type_rental DROP FOREIGN KEY FK_7405CB1642750E');
        $this->addSql('DROP INDEX UNIQ_7405CB1642750E ON balu_contract_type_rental');
        $this->addSql('ALTER TABLE balu_contract_type_rental CHANGE oobject_id object_id INT NOT NULL');
        $this->addSql('ALTER TABLE balu_contract_type_rental ADD CONSTRAINT FK_7405CB1232D562B FOREIGN KEY (object_id) REFERENCES balu_apartment (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7405CB1232D562B ON balu_contract_type_rental (object_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_contract_type_rental DROP FOREIGN KEY FK_7405CB1232D562B');
        $this->addSql('DROP INDEX UNIQ_7405CB1232D562B ON balu_contract_type_rental');
        $this->addSql('ALTER TABLE balu_contract_type_rental CHANGE object_id oobject_id INT NOT NULL');
        $this->addSql('ALTER TABLE balu_contract_type_rental ADD CONSTRAINT FK_7405CB1642750E FOREIGN KEY (oobject_id) REFERENCES balu_apartment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7405CB1642750E ON balu_contract_type_rental (oobject_id)');
    }
}
