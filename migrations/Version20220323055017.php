<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220323055017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_property ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_property ADD CONSTRAINT FK_DBB90EC3B03A8386 FOREIGN KEY (created_by_id) REFERENCES balu_user_identity (id)');
        $this->addSql('CREATE INDEX IDX_DBB90EC3B03A8386 ON balu_property (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_property DROP FOREIGN KEY FK_DBB90EC3B03A8386');
        $this->addSql('DROP INDEX IDX_DBB90EC3B03A8386 ON balu_property');
        $this->addSql('ALTER TABLE balu_property DROP created_by_id');
    }
}
