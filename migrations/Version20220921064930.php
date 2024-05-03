<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
 
final class Version20220921064930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    { 
        $this->addSql('CREATE TABLE balu_message_type (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name_en VARCHAR(25) NOT NULL, name_de VARCHAR(25) NOT NULL, type_key VARCHAR(25) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_E07CF396B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_message ADD type_id INT DEFAULT NULL, DROP type');
        $this->addSql('ALTER TABLE balu_message ADD CONSTRAINT FK_F8DE110FC54C8C93 FOREIGN KEY (type_id) REFERENCES balu_message_type (id)');
        $this->addSql('CREATE INDEX IDX_F8DE110FC54C8C93 ON balu_message (type_id)');
    }

    public function down(Schema $schema): void
    { 
        $this->addSql('ALTER TABLE balu_message DROP FOREIGN KEY FK_F8DE110FC54C8C93');
        $this->addSql('DROP TABLE balu_message_type');
        $this->addSql('DROP INDEX IDX_F8DE110FC54C8C93 ON balu_message');
        $this->addSql('ALTER TABLE balu_message ADD type SMALLINT NOT NULL, DROP type_id');
    }
}
