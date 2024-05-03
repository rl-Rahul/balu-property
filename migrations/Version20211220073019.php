<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211220073019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_address (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, street VARCHAR(180) DEFAULT NULL, street_number VARCHAR(180) DEFAULT NULL, city VARCHAR(180) DEFAULT NULL, state VARCHAR(180) DEFAULT NULL, country VARCHAR(180) DEFAULT NULL, country_code VARCHAR(20) DEFAULT NULL, zip_code VARCHAR(20) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, land_line VARCHAR(20) DEFAULT NULL, latitude VARCHAR(180) DEFAULT NULL, longitude VARCHAR(180) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_432D4EF1B5B48B91 (public_id), INDEX IDX_432D4EF1A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_address ADD CONSTRAINT FK_432D4EF1A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_user_identity ADD created_by_id INT DEFAULT NULL, ADD company_name VARCHAR(180) DEFAULT NULL, ADD website VARCHAR(180) DEFAULT NULL, ADD language VARCHAR(255) DEFAULT NULL, ADD confirmation_token VARCHAR(255) DEFAULT NULL, ADD dob DATE DEFAULT NULL, ADD is_policy_accepted TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE balu_user_identity ADD CONSTRAINT FK_2E0CFE96B03A8386 FOREIGN KEY (created_by_id) REFERENCES balu_user_identity (id)');
        $this->addSql('CREATE INDEX IDX_2E0CFE96B03A8386 ON balu_user_identity (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_address');
        $this->addSql('ALTER TABLE balu_user_identity DROP FOREIGN KEY FK_2E0CFE96B03A8386');
        $this->addSql('DROP INDEX IDX_2E0CFE96B03A8386 ON balu_user_identity');
        $this->addSql('ALTER TABLE balu_user_identity DROP created_by_id, DROP company_name, DROP website, DROP language, DROP confirmation_token, DROP dob, DROP is_policy_accepted');
    }
}
