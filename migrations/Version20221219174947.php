<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221219174947 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage_log ADD assigned_company_id INT DEFAULT NULL, ADD preferred_company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_damage_log ADD CONSTRAINT FK_BD578F43AF3A79A7 FOREIGN KEY (assigned_company_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_damage_log ADD CONSTRAINT FK_BD578F435A9107FF FOREIGN KEY (preferred_company_id) REFERENCES balu_user_identity (id)');
        $this->addSql('CREATE INDEX IDX_BD578F43AF3A79A7 ON balu_damage_log (assigned_company_id)');
        $this->addSql('CREATE INDEX IDX_BD578F435A9107FF ON balu_damage_log (preferred_company_id)');
        $this->addSql('ALTER TABLE balu_request_logger CHANGE request_param request_param JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_user CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage_log DROP FOREIGN KEY FK_BD578F43AF3A79A7');
        $this->addSql('ALTER TABLE balu_damage_log DROP FOREIGN KEY FK_BD578F435A9107FF');
        $this->addSql('DROP INDEX IDX_BD578F43AF3A79A7 ON balu_damage_log');
        $this->addSql('DROP INDEX IDX_BD578F435A9107FF ON balu_damage_log');
        $this->addSql('ALTER TABLE balu_damage_log DROP assigned_company_id, DROP preferred_company_id');
    }
}
