<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220223101638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_contract_type CHANGE mode_of_payment mode_of_payment VARCHAR(25) DEFAULT NULL, CHANGE total_object_value total_object_value DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_contract_type CHANGE mode_of_payment mode_of_payment VARCHAR(25) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE total_object_value total_object_value DOUBLE PRECISION NOT NULL');
    }
}
