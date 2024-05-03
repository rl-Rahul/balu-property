<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221221040504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_company_subscription_plan ADD stripe_one_time_plan VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_subscription_plan ADD stripe_one_time_plan VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_company_subscription_plan DROP stripe_one_time_plan');
        $this->addSql('ALTER TABLE balu_subscription_plan DROP stripe_one_time_plan');
    }
}
