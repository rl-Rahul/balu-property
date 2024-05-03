<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220120181450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_temp (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, old_user_id INT NOT NULL, UNIQUE INDEX UNIQ_F70A586A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_temp ADD CONSTRAINT FK_F70A586A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_temp');
    }
}
