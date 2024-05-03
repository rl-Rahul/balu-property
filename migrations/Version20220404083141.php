<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
    
final class Version20220404083141 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_tenant ADD additional_comment LONGTEXT DEFAULT NULL, ADD owner_vote TINYINT(1) DEFAULT NULL, ADD contract_term SMALLINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_tenant DROP additional_comment, DROP owner_vote, DROP contract_term');
    }
}
