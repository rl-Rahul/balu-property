<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231201073249 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_push_notification ADD message_de TEXT DEFAULT NULL AFTER message');
        $this->addSql('UPDATE balu_property_role_invitation SET deleted = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_push_notification DROP message_de');
    }
}
