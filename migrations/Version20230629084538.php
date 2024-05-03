<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230629084538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage_defect DROP INDEX unique_entries, ADD INDEX unique_entries (damage_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage_defect DROP INDEX unique_entries, ADD UNIQUE INDEX unique_entries (damage_id)');
    }
}
