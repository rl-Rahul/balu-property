<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
 
final class Version20220713053233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_message (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, damage_id INT DEFAULT NULL, apartment_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, type SMALLINT NOT NULL, archive TINYINT(1) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_F8DE110FB5B48B91 (public_id), INDEX IDX_F8DE110FB03A8386 (created_by_id), INDEX IDX_F8DE110F6CE425B7 (damage_id), INDEX IDX_F8DE110F176DFE85 (apartment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_message ADD CONSTRAINT FK_F8DE110FB03A8386 FOREIGN KEY (created_by_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_message ADD CONSTRAINT FK_F8DE110F6CE425B7 FOREIGN KEY (damage_id) REFERENCES balu_damage (id)');
        $this->addSql('ALTER TABLE balu_message ADD CONSTRAINT FK_F8DE110F176DFE85 FOREIGN KEY (apartment_id) REFERENCES balu_apartment (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_message');
    }
}
