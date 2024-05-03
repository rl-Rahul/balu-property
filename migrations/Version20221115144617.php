<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
 
final class Version20221115144617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    { 
        $this->addSql('ALTER TABLE balu_damage ADD current_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_damage ADD CONSTRAINT FK_766A708ED635610 FOREIGN KEY (current_user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('CREATE INDEX IDX_766A708ED635610 ON balu_damage (current_user_id)');
    }

    public function down(Schema $schema): void
    { 
        $this->addSql('ALTER TABLE balu_damage DROP FOREIGN KEY FK_766A708ED635610');
        $this->addSql('DROP INDEX IDX_766A708ED635610 ON balu_damage');
        $this->addSql('ALTER TABLE balu_damage DROP current_user_id');
    }
}
