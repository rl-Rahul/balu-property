<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220526072747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment CHANGE official_number official_number INT DEFAULT NULL');
        $this->addSql('ALTER TABLE  balu_object_contracts CHANGE rental_type_id rental_type_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment CHANGE official_number official_number INT NOT NULL');
        $this->addSql('ALTER TABLE balu_object_contracts CHANGE rental_type_id rental_type_id INT NOT NULL');
    }
}
