<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220211054746 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_property_group_mapping (property_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_E1C2055A549213EC (property_id), INDEX IDX_E1C2055AFE54D947 (group_id), PRIMARY KEY(property_id, group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_property_group_mapping ADD CONSTRAINT FK_E1C2055A549213EC FOREIGN KEY (property_id) REFERENCES balu_property_group (id)');
        $this->addSql('ALTER TABLE balu_property_group_mapping ADD CONSTRAINT FK_E1C2055AFE54D947 FOREIGN KEY (group_id) REFERENCES balu_property (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_property_group_mapping');
    }
}
