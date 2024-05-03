<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
 
final class Version20220902092407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    { 
        $this->addSql('ALTER TABLE balu_message ADD created_by_role_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_message ADD CONSTRAINT FK_F8DE110FC75DCAC FOREIGN KEY (created_by_role_id) REFERENCES balu_role (id)');
        $this->addSql('CREATE INDEX IDX_F8DE110FC75DCAC ON balu_message (created_by_role_id)');
    }

    public function down(Schema $schema): void
    { 
        $this->addSql('ALTER TABLE balu_message DROP FOREIGN KEY FK_F8DE110FC75DCAC');
        $this->addSql('DROP INDEX IDX_F8DE110FC75DCAC ON balu_message');
        $this->addSql('ALTER TABLE balu_message DROP created_by_role_id');
    }
}
