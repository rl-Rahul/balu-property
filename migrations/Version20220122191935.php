<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220122191935 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_user_subscription DROP INDEX UNIQ_A99A2D5C6BF9496, ADD INDEX IDX_A99A2D5C6BF9496 (company_subscription_plan_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_user_subscription DROP INDEX IDX_A99A2D5C6BF9496, ADD UNIQUE INDEX UNIQ_A99A2D5C6BF9496 (company_subscription_plan_id)');
    }
}
