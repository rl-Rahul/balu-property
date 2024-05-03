<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220324112322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment DROP INDEX UNIQ_2E284F9F854679E2, ADD INDEX IDX_2E284F9F854679E2 (floor_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment DROP INDEX IDX_2E284F9F854679E2, ADD UNIQUE INDEX UNIQ_2E284F9F854679E2 (floor_id)');
    }
}
