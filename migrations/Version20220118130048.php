<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220118130048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX unique_rating_entries ON balu_company_rating (company_id, damage_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX unique_rating_entries ON balu_company_rating');
    }
}
