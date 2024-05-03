<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230427103400 extends AbstractMigration
{
    public function getDescription(): string    
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_company_subscription_plan ADD color_code VARCHAR(10) DEFAULT NULL, ADD text_color VARCHAR(10) DEFAULT NULL, ADD details LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_company_subscription_plan DROP color_code, DROP text_color, DROP details');
    }
}
