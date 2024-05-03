<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230519101616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage ADD issue_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_damage ADD CONSTRAINT FK_766A708E60B4C972 FOREIGN KEY (issue_type_id) REFERENCES balu_category (id)');
        $this->addSql('CREATE INDEX IDX_766A708E60B4C972 ON balu_damage (issue_type_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage DROP FOREIGN KEY FK_766A708E60B4C972');
        $this->addSql('DROP INDEX IDX_766A708E60B4C972 ON balu_damage');
        $this->addSql('ALTER TABLE balu_damage DROP issue_type_id');
    }
}
