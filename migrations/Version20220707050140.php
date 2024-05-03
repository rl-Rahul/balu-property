<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220707050140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment_rent_history DROP rent_updated, DROP additional_cost_updated, DROP reference_rate_updated, DROP basis_land_index_updated, DROP actual_index_stand_updated, DROP actual_index_stand_number_updated, DROP payment_mode_updated');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment_rent_history ADD rent_updated TINYINT(1) NOT NULL, ADD additional_cost_updated TINYINT(1) NOT NULL, ADD reference_rate_updated TINYINT(1) NOT NULL, ADD basis_land_index_updated TINYINT(1) NOT NULL, ADD actual_index_stand_updated TINYINT(1) NOT NULL, ADD actual_index_stand_number_updated TINYINT(1) NOT NULL, ADD payment_mode_updated TINYINT(1) NOT NULL');
    }
}
