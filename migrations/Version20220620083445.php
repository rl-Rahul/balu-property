<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
 

final class Version20220620083445 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {   //fix: reserved name "key" changed to "status key". 
        $this->addSql('ALTER TABLE balu_damage_status CHANGE created_at created_at DATETIME NOT NULL, CHANGE deleted deleted TINYINT(1) NOT NULL, CHANGE public_id public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', CHANGE `key` status_key VARCHAR(180) NOT NULL');
     }

    public function down(Schema $schema): void
    { 
        $this->addSql('ALTER TABLE balu_damage_status CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE deleted deleted TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE public_id public_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', CHANGE statuskey `key` VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
