<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230313162123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_subscription_plan CHANGE details details LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_subscription_plan CHANGE details details LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
    }
}
