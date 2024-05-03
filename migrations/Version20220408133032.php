<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220408133032 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_tenant');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_tenant (id INT AUTO_INCREMENT NOT NULL, apartment_id INT DEFAULT NULL, added_by_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, contract_start_date DATETIME DEFAULT NULL, contract_end_date DATETIME DEFAULT NULL, notice_period_days INT DEFAULT NULL, active TINYINT(1) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', additional_comment LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, owner_vote TINYINT(1) DEFAULT NULL, contract_period_type SMALLINT DEFAULT NULL, INDEX IDX_29FBE080176DFE85 (apartment_id), INDEX IDX_29FBE08055B127A4 (added_by_id), UNIQUE INDEX UNIQ_29FBE080B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE balu_tenant ADD CONSTRAINT FK_29FBE080176DFE85 FOREIGN KEY (apartment_id) REFERENCES balu_apartment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_tenant ADD CONSTRAINT FK_29FBE08055B127A4 FOREIGN KEY (added_by_id) REFERENCES balu_user_identity (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
