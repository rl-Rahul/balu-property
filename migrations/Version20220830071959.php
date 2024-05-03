<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
 
final class Version20220830071959 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage ADD created_by_role_id INT DEFAULT NULL, ADD company_assigned_by_role_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_damage ADD CONSTRAINT FK_766A708EC75DCAC FOREIGN KEY (created_by_role_id) REFERENCES balu_role (id)');
        $this->addSql('ALTER TABLE balu_damage ADD CONSTRAINT FK_766A708ECE3D2286 FOREIGN KEY (company_assigned_by_role_id) REFERENCES balu_role (id)');
        $this->addSql('CREATE INDEX IDX_766A708EC75DCAC ON balu_damage (created_by_role_id)');
        $this->addSql('CREATE INDEX IDX_766A708ECE3D2286 ON balu_damage (company_assigned_by_role_id)');
       
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage DROP FOREIGN KEY FK_766A708EC75DCAC');
        $this->addSql('ALTER TABLE balu_damage DROP FOREIGN KEY FK_766A708ECE3D2286');
        $this->addSql('DROP INDEX IDX_766A708EC75DCAC ON balu_damage');
        $this->addSql('DROP INDEX IDX_766A708ECE3D2286 ON balu_damage');
        $this->addSql('ALTER TABLE balu_damage DROP created_by_role_id, DROP company_assigned_by_role_id');
    }
}
