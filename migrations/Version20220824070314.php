<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220824070314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_user_identity ADD company_subscription_plan_id INT DEFAULT NULL, ADD is_admin_blocked TINYINT(1) NOT NULL, ADD is_free_plan_subscribed TINYINT(1) NOT NULL, ADD is_recurring TINYINT(1) NOT NULL, ADD stripe_subscription VARCHAR(255) DEFAULT NULL, ADD is_expired TINYINT(1) NOT NULL, ADD expiry_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_user_identity ADD CONSTRAINT FK_2E0CFE96C6BF9496 FOREIGN KEY (company_subscription_plan_id) REFERENCES balu_company_subscription_plan (id)');
        $this->addSql('CREATE INDEX IDX_2E0CFE96C6BF9496 ON balu_user_identity (company_subscription_plan_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_user_identity DROP FOREIGN KEY FK_2E0CFE96C6BF9496');
        $this->addSql('DROP INDEX IDX_2E0CFE96C6BF9496 ON balu_user_identity');
        $this->addSql('ALTER TABLE balu_user_identity DROP company_subscription_plan_id, DROP is_admin_blocked, DROP is_free_plan_subscribed, DROP is_recurring, DROP stripe_subscription, DROP is_expired, DROP expiry_date');
    }
}
