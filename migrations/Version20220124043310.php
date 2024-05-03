<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220124043310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment_document ADD title VARCHAR(255) NOT NULL, ADD original_name VARCHAR(255) NOT NULL, ADD file_display_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_property_document CHANGE original_name original_name VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment_document DROP title, DROP original_name, DROP file_display_name');
        $this->addSql('ALTER TABLE balu_property_document CHANGE original_name original_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
