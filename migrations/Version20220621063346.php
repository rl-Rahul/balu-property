<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220621063346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_document ADD contract_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_document ADD CONSTRAINT FK_8822986B2576E0FD FOREIGN KEY (contract_id) REFERENCES balu_object_contracts (id)');
        $this->addSql('CREATE INDEX IDX_8822986B2576E0FD ON balu_document (contract_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_document DROP FOREIGN KEY FK_8822986B2576E0FD');
        $this->addSql('DROP INDEX IDX_8822986B2576E0FD ON balu_document');
        $this->addSql('ALTER TABLE balu_document DROP contract_id');
    }
}
