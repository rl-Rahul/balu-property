<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230417085011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_subscription_history ADD subscription_plan_id INT DEFAULT NULL, ADD start_date DATETIME DEFAULT NULL, ADD end_date DATETIME DEFAULT NULL, ADD cancelled_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_subscription_history ADD CONSTRAINT FK_248ABCE99B8CE200 FOREIGN KEY (subscription_plan_id) REFERENCES balu_subscription_plan (id)');
        $this->addSql('CREATE INDEX IDX_248ABCE99B8CE200 ON balu_subscription_history (subscription_plan_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_subscription_history DROP FOREIGN KEY FK_248ABCE99B8CE200');
        $this->addSql('DROP INDEX IDX_248ABCE99B8CE200 ON balu_subscription_history');
        $this->addSql('ALTER TABLE balu_subscription_history DROP subscription_plan_id, DROP start_date, DROP end_date, DROP cancelled_date');
    }
}
