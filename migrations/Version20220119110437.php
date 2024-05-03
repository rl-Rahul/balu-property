<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220119110437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_property_document ADD file_display_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_property_document ADD original_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_property_document DROP file_display_name');
        $this->addSql('ALTER TABLE balu_property_document DROP original_name');
    }
}
