<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220519054555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_property_document ADD property_id INT DEFAULT NULL, ADD folder_id INT NOT NULL');
        $this->addSql('ALTER TABLE balu_property_document ADD CONSTRAINT FK_AF7D0436549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id)');
        $this->addSql('ALTER TABLE balu_property_document ADD CONSTRAINT FK_AF7D0436162CB942 FOREIGN KEY (folder_id) REFERENCES balu_folder (id)');
        $this->addSql('CREATE INDEX IDX_AF7D0436549213EC ON balu_property_document (property_id)');
        $this->addSql('CREATE INDEX IDX_AF7D0436162CB942 ON balu_property_document (folder_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_object_contracts RENAME INDEX uniq_9c0a8d162cb942 TO UNIQ_9C0A8DF1595FC8');
        $this->addSql('ALTER TABLE balu_property_document DROP FOREIGN KEY FK_AF7D0436549213EC');
        $this->addSql('ALTER TABLE balu_property_document DROP FOREIGN KEY FK_AF7D0436162CB942');
        $this->addSql('DROP INDEX IDX_AF7D0436549213EC ON balu_property_document');
        $this->addSql('DROP INDEX IDX_AF7D0436162CB942 ON balu_property_document');
        $this->addSql('ALTER TABLE balu_property_document DROP property_id, DROP folder_id');
    }
}
