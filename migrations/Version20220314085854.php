<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220314085854 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS balu_object_type_measure');
        $this->addSql('ALTER TABLE balu_property CHANGE pending_payment pending_payment TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE balu_property_group_mapping DROP FOREIGN KEY FK_E1C2055A549213EC');
        $this->addSql('ALTER TABLE balu_property_group_mapping ADD CONSTRAINT FK_E1C2055A549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_object_type_measure (id INT AUTO_INCREMENT NOT NULL, object_id INT NOT NULL, object_type_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, area DOUBLE PRECISION DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_8449B2232D562B (object_id), INDEX IDX_8449B2C5020C33 (object_type_id), UNIQUE INDEX UNIQ_8449B2B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE balu_object_type_measure ADD CONSTRAINT FK_8449B2232D562B FOREIGN KEY (object_id) REFERENCES balu_apartment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_object_type_measure ADD CONSTRAINT FK_8449B2C5020C33 FOREIGN KEY (object_type_id) REFERENCES balu_object_types (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_property CHANGE pending_payment pending_payment TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE balu_property_group_mapping DROP FOREIGN KEY FK_E1C2055A549213EC');
        $this->addSql('ALTER TABLE balu_property_group_mapping ADD CONSTRAINT FK_E1C2055A549213EC FOREIGN KEY (property_id) REFERENCES balu_property_group (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
