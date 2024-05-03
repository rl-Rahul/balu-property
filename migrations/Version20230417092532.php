<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230417092532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_payment ADD subscription_plan_id INT DEFAULT NULL, ADD company_plan_id INT DEFAULT NULL, ADD start_date DATETIME DEFAULT NULL, ADD end_date DATETIME DEFAULT NULL, ADD cancelled_date DATETIME DEFAULT NULL, ADD event_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_payment ADD CONSTRAINT FK_234BA57D9B8CE200 FOREIGN KEY (subscription_plan_id) REFERENCES balu_subscription_plan (id)');
        $this->addSql('ALTER TABLE balu_payment ADD CONSTRAINT FK_234BA57DC8CB184C FOREIGN KEY (company_plan_id) REFERENCES balu_company_subscription_plan (id)');
        $this->addSql('CREATE INDEX IDX_234BA57D9B8CE200 ON balu_payment (subscription_plan_id)');
        $this->addSql('CREATE INDEX IDX_234BA57DC8CB184C ON balu_payment (company_plan_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_payment DROP FOREIGN KEY FK_234BA57D9B8CE200');
        $this->addSql('ALTER TABLE balu_payment DROP FOREIGN KEY FK_234BA57DC8CB184C');
        $this->addSql('DROP INDEX IDX_234BA57D9B8CE200 ON balu_payment');
        $this->addSql('DROP INDEX IDX_234BA57DC8CB184C ON balu_payment');
        $this->addSql('ALTER TABLE balu_payment DROP subscription_plan_id, DROP company_plan_id, DROP start_date, DROP end_date, DROP cancelled_date, DROP event_type');
    }
}
