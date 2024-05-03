<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220707095329 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment_rent_history ADD mode_of_payment_id INT DEFAULT NULL, DROP payment_mode');
        $this->addSql('ALTER TABLE balu_apartment_rent_history ADD CONSTRAINT FK_80EC4A1AC9A9CD82 FOREIGN KEY (mode_of_payment_id) REFERENCES balu_mode_of_payment (id)');
        $this->addSql('CREATE INDEX IDX_80EC4A1AC9A9CD82 ON balu_apartment_rent_history (mode_of_payment_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment_rent_history DROP FOREIGN KEY FK_80EC4A1AC9A9CD82');
        $this->addSql('DROP INDEX IDX_80EC4A1AC9A9CD82 ON balu_apartment_rent_history');
        $this->addSql('ALTER TABLE balu_apartment_rent_history ADD payment_mode VARCHAR(180) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP mode_of_payment_id');
    }
}
