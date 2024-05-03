<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240423051243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_message_read_user (id INT AUTO_INCREMENT NOT NULL, message_id INT DEFAULT NULL, damage_id INT DEFAULT NULL, role_id INT DEFAULT NULL, user_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, is_read TINYINT(1) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_4B4DB642B5B48B91 (public_id), INDEX IDX_4B4DB642537A1329 (message_id), INDEX IDX_4B4DB6426CE425B7 (damage_id), INDEX IDX_4B4DB642D60322AC (role_id), INDEX IDX_4B4DB642A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_message_read_user ADD CONSTRAINT FK_4B4DB642537A1329 FOREIGN KEY (message_id) REFERENCES balu_message (id)');
        $this->addSql('ALTER TABLE balu_message_read_user ADD CONSTRAINT FK_4B4DB6426CE425B7 FOREIGN KEY (damage_id) REFERENCES balu_damage (id)');
        $this->addSql('ALTER TABLE balu_message_read_user ADD CONSTRAINT FK_4B4DB642D60322AC FOREIGN KEY (role_id) REFERENCES balu_role (id)');
        $this->addSql('ALTER TABLE balu_message_read_user ADD CONSTRAINT FK_4B4DB642A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_message DROP FOREIGN KEY FK_F8DE110FCAACDA2C');
        $this->addSql('DROP INDEX UNIQ_F8DE110FCAACDA2C ON balu_message');
        $this->addSql('ALTER TABLE balu_message DROP message_read_users_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_message_read_user DROP FOREIGN KEY FK_4B4DB642537A1329');
        $this->addSql('ALTER TABLE balu_message_read_user DROP FOREIGN KEY FK_4B4DB6426CE425B7');
        $this->addSql('ALTER TABLE balu_message_read_user DROP FOREIGN KEY FK_4B4DB642D60322AC');
        $this->addSql('ALTER TABLE balu_message_read_user DROP FOREIGN KEY FK_4B4DB642A76ED395');
        $this->addSql('DROP TABLE balu_message_read_user');
        $this->addSql('ALTER TABLE balu_folder ADD CONSTRAINT FK_8B002D2F906930EA FOREIGN KEY (created_role_id) REFERENCES balu_role (id)');
        $this->addSql('ALTER TABLE balu_message ADD message_read_users_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_message ADD CONSTRAINT FK_F8DE110FCAACDA2C FOREIGN KEY (message_read_users_id) REFERENCES balu_message_read_user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F8DE110FCAACDA2C ON balu_message (message_read_users_id)');
    }
}
