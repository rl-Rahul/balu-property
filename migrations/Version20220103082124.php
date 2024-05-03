<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220103082124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_permission (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name VARCHAR(45) NOT NULL, `key` VARCHAR(45) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_EC9CDFB5B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_role (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name VARCHAR(45) DEFAULT NULL, `key` VARCHAR(45) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_534AAA26B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_role_permission (role_id INT NOT NULL, permission_id INT NOT NULL, INDEX IDX_C344C0E2D60322AC (role_id), INDEX IDX_C344C0E2FED90CCA (permission_id), PRIMARY KEY(role_id, permission_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_user_identity_role (user_identity_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_30AEB91D56251D3D (user_identity_id), INDEX IDX_30AEB91DD60322AC (role_id), PRIMARY KEY(user_identity_id, role_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_role_permission ADD CONSTRAINT FK_C344C0E2D60322AC FOREIGN KEY (role_id) REFERENCES balu_role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balu_role_permission ADD CONSTRAINT FK_C344C0E2FED90CCA FOREIGN KEY (permission_id) REFERENCES balu_permission (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balu_user_identity_role ADD CONSTRAINT FK_30AEB91D56251D3D FOREIGN KEY (user_identity_id) REFERENCES balu_user_identity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balu_user_identity_role ADD CONSTRAINT FK_30AEB91DD60322AC FOREIGN KEY (role_id) REFERENCES balu_role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balu_user ADD confirmation_token LONGTEXT DEFAULT NULL, ADD is_token_verified TINYINT(1) DEFAULT \'0\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_role_permission DROP FOREIGN KEY FK_C344C0E2FED90CCA');
        $this->addSql('ALTER TABLE balu_role_permission DROP FOREIGN KEY FK_C344C0E2D60322AC');
        $this->addSql('ALTER TABLE balu_user_identity_role DROP FOREIGN KEY FK_30AEB91DD60322AC');
        $this->addSql('DROP TABLE balu_permission');
        $this->addSql('DROP TABLE balu_role');
        $this->addSql('DROP TABLE balu_role_permission');
        $this->addSql('DROP TABLE balu_user_identity_role');
        $this->addSql('ALTER TABLE balu_user DROP confirmation_token, DROP is_token_verified');
    }
}
