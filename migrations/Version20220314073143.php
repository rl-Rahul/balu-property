<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220314073143 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('ALTER TABLE balu_property_group_mapping DROP FOREIGN KEY FK_E1C2055AFE54D947');
        $this->addSql('ALTER TABLE balu_property_group_mapping ADD CONSTRAINT FK_E1C2055AFE54D947 FOREIGN KEY (group_id) REFERENCES balu_property_group (id)');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_property_group_mapping DROP FOREIGN KEY FK_E1C2055AFE54D947');
        $this->addSql('ALTER TABLE balu_property_group_mapping ADD CONSTRAINT FK_E1C2055AFE54D947 FOREIGN KEY (group_id) REFERENCES balu_property (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
