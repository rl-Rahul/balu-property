<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220119052757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_property_attachments (property_id INT NOT NULL, document_id INT NOT NULL, INDEX IDX_888416D9549213EC (property_id), INDEX IDX_888416D9C33F7837 (document_id), PRIMARY KEY(property_id, document_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_property_attachments ADD CONSTRAINT FK_888416D9549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id)');
        $this->addSql('ALTER TABLE balu_property_attachments ADD CONSTRAINT FK_888416D9C33F7837 FOREIGN KEY (document_id) REFERENCES balu_property_document (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_property_attachments');
    }
}
