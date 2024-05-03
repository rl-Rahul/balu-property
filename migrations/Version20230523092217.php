<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230523092217 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage DROP is_offer_preferred');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage ADD is_offer_preferred TINYINT(1) NOT NULL');
    }
}