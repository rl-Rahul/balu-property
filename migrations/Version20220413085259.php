<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220413085259 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_notice_period (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name_en VARCHAR(255) DEFAULT NULL, name_de VARCHAR(255) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_3AB8AB01B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_object_contracts (id INT AUTO_INCREMENT NOT NULL, object_id INT NOT NULL, notice_period_id INT DEFAULT NULL, rental_type_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, rental_start_date DATETIME DEFAULT NULL, rental_end_date DATETIME DEFAULT NULL, additional_comment LONGTEXT DEFAULT NULL, owner_vote TINYINT(1) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_9C0A8DB5B48B91 (public_id), INDEX IDX_9C0A8D232D562B (object_id), UNIQUE INDEX UNIQ_9C0A8DA5B96DD8 (notice_period_id), UNIQUE INDEX UNIQ_9C0A8D16AA567C (rental_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_rental_types (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name VARCHAR(255) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_7EC38B9B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_object_contracts ADD CONSTRAINT FK_9C0A8D232D562B FOREIGN KEY (object_id) REFERENCES balu_apartment (id)');
        $this->addSql('ALTER TABLE balu_object_contracts ADD CONSTRAINT FK_9C0A8DA5B96DD8 FOREIGN KEY (notice_period_id) REFERENCES balu_notice_period (id)');
        $this->addSql('ALTER TABLE balu_object_contracts ADD CONSTRAINT FK_9C0A8D16AA567C FOREIGN KEY (rental_type_id) REFERENCES balu_rental_types (id)');
        $this->addSql('ALTER TABLE balu_apartment DROP FOREIGN KEY FK_2E284F9FCD1DF15B');
        $this->addSql('DROP INDEX IDX_2E284F9FCD1DF15B ON balu_apartment');
        $this->addSql('ALTER TABLE balu_apartment DROP contract_type_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_object_contracts DROP FOREIGN KEY FK_9C0A8DA5B96DD8');
        $this->addSql('ALTER TABLE balu_object_contracts DROP FOREIGN KEY FK_9C0A8D16AA567C');
        $this->addSql('DROP TABLE balu_notice_period');
        $this->addSql('DROP TABLE balu_object_contracts');
        $this->addSql('DROP TABLE balu_rental_types');
        $this->addSql('ALTER TABLE balu_apartment ADD contract_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_apartment ADD CONSTRAINT FK_2E284F9FCD1DF15B FOREIGN KEY (contract_type_id) REFERENCES balu_contract_types (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_2E284F9FCD1DF15B ON balu_apartment (contract_type_id)');
    }
}
