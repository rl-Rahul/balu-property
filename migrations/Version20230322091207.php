<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230322091207 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_reset_object CHANGE is_super_admin_approved is_super_admin_approved TINYINT(1) DEFAULT NULL, CHANGE super_admin_comment super_admin_comment LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_reset_object CHANGE is_super_admin_approved is_super_admin_approved TINYINT(1) NOT NULL, CHANGE super_admin_comment super_admin_comment LONGTEXT NOT NULL');
    }
}
