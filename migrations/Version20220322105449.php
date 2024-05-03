<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220322105449 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment DROP FOREIGN KEY FK_2E284F9FBBE93A37');
        $this->addSql('DROP INDEX IDX_2E284F9FBBE93A37 ON balu_apartment');
        $this->addSql('ALTER TABLE balu_apartment ADD room_count INT DEFAULT NULL, ADD ceiling_height INT DEFAULT NULL, ADD volume INT DEFAULT NULL, ADD max_floor_loading INT DEFAULT NULL, ADD official_number INT NOT NULL, DROP additional_cost, DROP actual_index_stand, DROP payment_mode, DROP actual_index_stand_number, DROP rooms, DROP floor, DROP floor_name, CHANGE reference_rate_id floor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_apartment ADD CONSTRAINT FK_2E284F9F854679E2 FOREIGN KEY (floor_id) REFERENCES balu_floor (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2E284F9F854679E2 ON balu_apartment (floor_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment DROP FOREIGN KEY FK_2E284F9F854679E2');
        $this->addSql('DROP INDEX UNIQ_2E284F9F854679E2 ON balu_apartment');
        $this->addSql('ALTER TABLE balu_apartment ADD reference_rate_id INT DEFAULT NULL, ADD additional_cost DOUBLE PRECISION DEFAULT NULL, ADD actual_index_stand VARCHAR(180) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD payment_mode VARCHAR(180) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD actual_index_stand_number DOUBLE PRECISION DEFAULT NULL, ADD rooms VARCHAR(25) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD floor VARCHAR(180) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD floor_name VARCHAR(250) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP floor_id, DROP room_count, DROP ceiling_height, DROP volume, DROP max_floor_loading, DROP official_number');
        $this->addSql('ALTER TABLE balu_apartment ADD CONSTRAINT FK_2E284F9FBBE93A37 FOREIGN KEY (reference_rate_id) REFERENCES balu_reference_index (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_2E284F9FBBE93A37 ON balu_apartment (reference_rate_id)');
    }
}
