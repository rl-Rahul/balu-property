<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230518072732 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage ADD allocation TINYINT(1) NOT NULL COMMENT \'Ticket allocation\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage DROP allocation');
    }
}
