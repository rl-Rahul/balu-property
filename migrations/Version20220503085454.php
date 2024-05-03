<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220503085454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_object_contracts ADD start_date DATETIME DEFAULT NULL, ADD end_date DATETIME DEFAULT NULL, DROP rental_start_date, DROP rental_end_date');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_object_contracts ADD rental_start_date DATETIME DEFAULT NULL, ADD rental_end_date DATETIME DEFAULT NULL, DROP start_date, DROP end_date');
    }
}
