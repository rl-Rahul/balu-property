<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220224102158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_property ADD janitor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_property ADD CONSTRAINT FK_DBB90EC366FF09E9 FOREIGN KEY (janitor_id) REFERENCES balu_user_identity (id)');
        $this->addSql('CREATE INDEX IDX_DBB90EC366FF09E9 ON balu_property (janitor_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_property DROP FOREIGN KEY FK_DBB90EC366FF09E9');
        $this->addSql('DROP INDEX IDX_DBB90EC366FF09E9 ON balu_property');
        $this->addSql('ALTER TABLE balu_property DROP janitor_id');
    }
}
