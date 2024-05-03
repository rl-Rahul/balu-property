<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231020062902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_role ADD name_de VARCHAR(45) DEFAULT NULL AFTER name');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_role DROP name_de');
    }
}
