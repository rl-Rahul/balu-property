<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220629091455 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_object_contracts_log DROP FOREIGN KEY FK_D045F19162CB942');
        $this->addSql('ALTER TABLE balu_object_contracts_log DROP FOREIGN KEY FK_D045F19232D562B');
        $this->addSql('DROP INDEX IDX_D045F19232D562B ON balu_object_contracts_log');
        $this->addSql('DROP INDEX UNIQ_D045F19162CB942 ON balu_object_contracts_log');
        $this->addSql('ALTER TABLE balu_object_contracts_log DROP object_id, DROP folder_id, DROP active');
        $this->addSql('ALTER TABLE balu_object_contracts_log_user ADD CONSTRAINT FK_9C8F9AC4EA675D86 FOREIGN KEY (log_id) REFERENCES balu_object_contracts_log (id)');
        $this->addSql('ALTER TABLE balu_object_contracts_log_user ADD CONSTRAINT FK_9C8F9AC4A76ED395 FOREIGN KEY (user_id) REFERENCES balu_property_user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_object_contracts_log ADD object_id INT NOT NULL, ADD folder_id INT DEFAULT NULL, ADD active TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_object_contracts_log ADD CONSTRAINT FK_D045F19162CB942 FOREIGN KEY (folder_id) REFERENCES balu_folder (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_object_contracts_log ADD CONSTRAINT FK_D045F19232D562B FOREIGN KEY (object_id) REFERENCES balu_apartment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_D045F19232D562B ON balu_object_contracts_log (object_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D045F19162CB942 ON balu_object_contracts_log (folder_id)');
        $this->addSql('ALTER TABLE balu_object_contracts_log_user DROP FOREIGN KEY FK_9C8F9AC4EA675D86');
        $this->addSql('ALTER TABLE balu_object_contracts_log_user DROP FOREIGN KEY FK_9C8F9AC4A76ED395');
    }
}
