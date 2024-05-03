<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220405070039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_tenant ADD contract_period_type SMALLINT DEFAULT NULL, DROP fixed_term_contract');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_tenant ADD fixed_term_contract TINYINT(1) NOT NULL, DROP contract_period_type');
    }
}
