<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220122185306 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_user ADD first_login DATETIME DEFAULT NULL, ADD web_first_login DATETIME DEFAULT NULL, ADD is_password_changed TINYINT(1) DEFAULT \'0\'');
        $this->addSql('ALTER TABLE balu_user_identity ADD parent_id INT DEFAULT NULL, CHANGE confirmation_token job_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_user_identity ADD CONSTRAINT FK_2E0CFE96727ACA70 FOREIGN KEY (parent_id) REFERENCES balu_user_identity (id)');
        $this->addSql('CREATE INDEX IDX_2E0CFE96727ACA70 ON balu_user_identity (parent_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_user DROP first_login, DROP web_first_login, DROP is_password_changed');
        $this->addSql('ALTER TABLE balu_user_identity DROP FOREIGN KEY FK_2E0CFE96727ACA70');
        $this->addSql('DROP INDEX IDX_2E0CFE96727ACA70 ON balu_user_identity');
        $this->addSql('ALTER TABLE balu_user_identity DROP parent_id, CHANGE job_title confirmation_token VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
