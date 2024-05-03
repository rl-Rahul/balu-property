<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
 
final class Version20220713061211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    { 
        $this->addSql('CREATE TABLE balu_message_apartment (message_id INT NOT NULL, apartment_id INT NOT NULL, INDEX IDX_E54D466537A1329 (message_id), INDEX IDX_E54D466176DFE85 (apartment_id), PRIMARY KEY(message_id, apartment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_message_apartment ADD CONSTRAINT FK_E54D466537A1329 FOREIGN KEY (message_id) REFERENCES balu_message (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balu_message_apartment ADD CONSTRAINT FK_E54D466176DFE85 FOREIGN KEY (apartment_id) REFERENCES balu_apartment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balu_message DROP FOREIGN KEY FK_F8DE110F176DFE85');
        $this->addSql('DROP INDEX IDX_F8DE110F176DFE85 ON balu_message');
        $this->addSql('ALTER TABLE balu_message DROP apartment_id');
    }

    public function down(Schema $schema): void
    { 
        $this->addSql('DROP TABLE balu_message_apartment');
        $this->addSql('ALTER TABLE balu_message ADD apartment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_message ADD CONSTRAINT FK_F8DE110F176DFE85 FOREIGN KEY (apartment_id) REFERENCES balu_apartment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_F8DE110F176DFE85 ON balu_message (apartment_id)');
    }
}
