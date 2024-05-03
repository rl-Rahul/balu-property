<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20221213063655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage ADD current_user_role_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_damage ADD CONSTRAINT FK_766A708E71BF827D FOREIGN KEY (current_user_role_id) REFERENCES balu_role (id)');
        $this->addSql('CREATE INDEX IDX_766A708E71BF827D ON balu_damage (current_user_role_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage DROP FOREIGN KEY FK_766A708E71BF827D');
        $this->addSql('DROP INDEX IDX_766A708E71BF827D ON balu_damage');
        $this->addSql('ALTER TABLE balu_damage DROP current_user_role_id');
    }
}
