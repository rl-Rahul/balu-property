<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230512144825 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }
    /**
     * 
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_user_identity ADD plan_end_date DATETIME DEFAULT NULL');
    }
    
    /**
     * 
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_user_identity DROP plan_end_date');
    }
}