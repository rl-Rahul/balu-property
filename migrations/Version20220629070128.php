<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220629070128 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_object_contracts_log (id INT AUTO_INCREMENT NOT NULL, contract_id INT NOT NULL, updated_by_id INT NOT NULL, object_id INT NOT NULL, notice_period_id INT DEFAULT NULL, rental_type_id INT DEFAULT NULL, folder_id INT DEFAULT NULL, terminated_by_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, additional_comment LONGTEXT DEFAULT NULL, owner_vote TINYINT(1) DEFAULT NULL, active TINYINT(1) DEFAULT NULL, notice_receipt_date DATETIME DEFAULT NULL, termination_date DATETIME DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_D045F19B5B48B91 (public_id), INDEX IDX_D045F192576E0FD (contract_id), INDEX IDX_D045F19896DBBDE (updated_by_id), INDEX IDX_D045F19232D562B (object_id), INDEX IDX_D045F19A5B96DD8 (notice_period_id), INDEX IDX_D045F1916AA567C (rental_type_id), UNIQUE INDEX UNIQ_D045F19162CB942 (folder_id), INDEX IDX_D045F1986573F49 (terminated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_object_contracts_log ADD CONSTRAINT FK_D045F192576E0FD FOREIGN KEY (contract_id) REFERENCES balu_object_contracts (id)');
        $this->addSql('ALTER TABLE balu_object_contracts_log ADD CONSTRAINT FK_D045F19896DBBDE FOREIGN KEY (updated_by_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_object_contracts_log ADD CONSTRAINT FK_D045F19232D562B FOREIGN KEY (object_id) REFERENCES balu_apartment (id)');
        $this->addSql('ALTER TABLE balu_object_contracts_log ADD CONSTRAINT FK_D045F19A5B96DD8 FOREIGN KEY (notice_period_id) REFERENCES balu_notice_period (id)');
        $this->addSql('ALTER TABLE balu_object_contracts_log ADD CONSTRAINT FK_D045F1916AA567C FOREIGN KEY (rental_type_id) REFERENCES balu_rental_types (id)');
        $this->addSql('ALTER TABLE balu_object_contracts_log ADD CONSTRAINT FK_D045F19162CB942 FOREIGN KEY (folder_id) REFERENCES balu_folder (id)');
        $this->addSql('ALTER TABLE balu_object_contracts_log ADD CONSTRAINT FK_D045F1986573F49 FOREIGN KEY (terminated_by_id) REFERENCES balu_user_identity (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_object_contracts_log');
    }
}
