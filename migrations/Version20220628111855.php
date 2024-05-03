<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
 
final class Version20220628111855 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
         $this->addSql('ALTER TABLE balu_damage ADD is_offer_preferred TINYINT(1) NOT NULL');   
         $this->addSql('ALTER TABLE balu_damage_image ADD is_editable TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
         $this->addSql('ALTER TABLE balu_damage DROP is_offer_preferred'); 
         $this->addSql('ALTER TABLE balu_damage_image DROP is_editable'); 
    }
}
