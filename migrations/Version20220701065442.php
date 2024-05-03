<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
 
final class Version20220701065442 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_push_notification ADD event VARCHAR(255) DEFAULT NULL AFTER message');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_push_notification DROP event');
    }
}
