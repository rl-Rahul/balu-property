<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220526100108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_property_user DROP FOREIGN KEY FK_369F9A212C94069F');
        $this->addSql('DROP INDEX IDX_369F9A212C94069F ON balu_property_user');
        $this->addSql('ALTER TABLE balu_property_user CHANGE directory_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_property_user ADD CONSTRAINT FK_369F9A21A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('CREATE INDEX IDX_369F9A21A76ED395 ON balu_property_user (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_property_user DROP FOREIGN KEY FK_369F9A21A76ED395');
        $this->addSql('DROP INDEX IDX_369F9A21A76ED395 ON balu_property_user');
        $this->addSql('ALTER TABLE balu_property_user CHANGE user_id directory_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_property_user ADD CONSTRAINT FK_369F9A212C94069F FOREIGN KEY (directory_id) REFERENCES balu_directory (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_369F9A212C94069F ON balu_property_user (directory_id)');
    }
}
