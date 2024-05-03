<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230530102031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS balu_company_offer_request');
        $this->addSql('CREATE TABLE balu_damage_request (id INT AUTO_INCREMENT NOT NULL, damage_id INT NOT NULL, company_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, requested_date DATETIME DEFAULT NULL, new_offer_requested_date DATETIME DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_3AFB1072B5B48B91 (public_id), INDEX IDX_3AFB10726CE425B7 (damage_id), INDEX IDX_3AFB1072979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_damage_request ADD CONSTRAINT FK_3AFB10726CE425B7 FOREIGN KEY (damage_id) REFERENCES balu_damage (id)');
        $this->addSql('ALTER TABLE balu_damage_request ADD CONSTRAINT FK_3AFB1072979B1AD6 FOREIGN KEY (company_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_damage_offer ADD damage_request_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_damage_offer ADD accepted_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_damage_offer ADD CONSTRAINT FK_7A61FD40780AFAF5 FOREIGN KEY (damage_request_id) REFERENCES balu_damage_request (id)');
        $this->addSql('CREATE INDEX IDX_7A61FD40780AFAF5 ON balu_damage_offer (damage_request_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage_offer DROP FOREIGN KEY FK_7A61FD40780AFAF5');
        $this->addSql('DROP TABLE balu_damage_request');
        $this->addSql('DROP INDEX IDX_7A61FD40780AFAF5 ON balu_damage_offer');
        $this->addSql('ALTER TABLE balu_damage_offer DROP damage_request_id');
    }
}
