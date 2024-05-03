<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220703062327 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_object_contracts_log_user DROP FOREIGN KEY FK_9C8F9AC4A76ED395');
        $this->addSql('ALTER TABLE balu_object_contracts_log_user ADD contract_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_object_contracts_log_user ADD CONSTRAINT FK_9C8F9AC42576E0FD FOREIGN KEY (contract_id) REFERENCES balu_object_contracts (id)');
        $this->addSql('ALTER TABLE balu_object_contracts_log_user ADD CONSTRAINT FK_9C8F9AC4A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('CREATE INDEX IDX_9C8F9AC42576E0FD ON balu_object_contracts_log_user (contract_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_object_contracts_log_user DROP FOREIGN KEY FK_9C8F9AC42576E0FD');
        $this->addSql('ALTER TABLE balu_object_contracts_log_user DROP FOREIGN KEY FK_9C8F9AC4A76ED395');
        $this->addSql('DROP INDEX IDX_9C8F9AC42576E0FD ON balu_object_contracts_log_user');
        $this->addSql('ALTER TABLE balu_object_contracts_log_user DROP contract_id');
        $this->addSql('ALTER TABLE balu_object_contracts_log_user ADD CONSTRAINT FK_9C8F9AC4A76ED395 FOREIGN KEY (user_id) REFERENCES balu_property_user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
