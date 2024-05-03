<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230323094941 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_reset_object ADD property_id INT NOT NULL, CHANGE is_super_admin_approved is_super_admin_approved TINYINT(1) DEFAULT 0');
        $this->addSql('ALTER TABLE balu_reset_object ADD CONSTRAINT FK_B8722A13549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id)');
        $this->addSql('CREATE INDEX IDX_B8722A13549213EC ON balu_reset_object (property_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_reset_object DROP FOREIGN KEY FK_B8722A13549213EC');
        $this->addSql('DROP INDEX IDX_B8722A13549213EC ON balu_reset_object');
        $this->addSql('ALTER TABLE balu_reset_object DROP property_id, CHANGE is_super_admin_approved is_super_admin_approved TINYINT(1) DEFAULT NULL');
    }
}
