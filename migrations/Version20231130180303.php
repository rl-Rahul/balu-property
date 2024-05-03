<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231130180303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_company_subscription_plan ADD name_de VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE balu_subscription_plan ADD name_de VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_company_subscription_plan DROP name_de');
        $this->addSql('ALTER TABLE balu_subscription_plan DROP name_de');
    }
}
