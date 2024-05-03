<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220111054524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_category (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name VARCHAR(180) DEFAULT NULL, name_de VARCHAR(180) DEFAULT NULL, sort_order VARCHAR(180) DEFAULT NULL, icon VARCHAR(180) DEFAULT NULL, active TINYINT(1) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_56070BDCB5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_category_user_identity (category_id INT NOT NULL, user_identity_id INT NOT NULL, INDEX IDX_A89FB9F312469DE2 (category_id), INDEX IDX_A89FB9F356251D3D (user_identity_id), PRIMARY KEY(category_id, user_identity_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_category_user_identity ADD CONSTRAINT FK_A89FB9F312469DE2 FOREIGN KEY (category_id) REFERENCES balu_category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balu_category_user_identity ADD CONSTRAINT FK_A89FB9F356251D3D FOREIGN KEY (user_identity_id) REFERENCES balu_user_identity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balu_user ADD password_requested_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_user_identity ADD is_blocked TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_category_user_identity DROP FOREIGN KEY FK_A89FB9F312469DE2');
        $this->addSql('DROP TABLE balu_category');
        $this->addSql('DROP TABLE balu_category_user_identity');
        $this->addSql('ALTER TABLE balu_user DROP password_requested_at');
        $this->addSql('ALTER TABLE balu_user_identity DROP is_blocked');
    }
}
