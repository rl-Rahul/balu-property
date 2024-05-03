<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220407110422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_property_document ADD file_path VARCHAR(255) DEFAULT NULL, ADD mime_type VARCHAR(100) DEFAULT NULL, ADD file_size DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_temporary_upload ADD local_file_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_property_document DROP file_path, DROP mime_type, DROP file_size');
        $this->addSql('ALTER TABLE balu_temporary_upload DROP local_file_name');
    }
}
