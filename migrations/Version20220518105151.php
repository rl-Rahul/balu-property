<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220518105151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment_document ADD is_public TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE balu_property_document ADD is_public TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment_document DROP is_public');
        $this->addSql('ALTER TABLE balu_property_document DROP is_public');
    }
}
