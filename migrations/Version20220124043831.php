<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220124043831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment_document ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE balu_apartment_document ADD CONSTRAINT FK_D2CC8105A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('CREATE INDEX IDX_D2CC8105A76ED395 ON balu_apartment_document (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment_document DROP FOREIGN KEY FK_D2CC8105A76ED395');
        $this->addSql('DROP INDEX IDX_D2CC8105A76ED395 ON balu_apartment_document');
        $this->addSql('ALTER TABLE balu_apartment_document DROP user_id');
    }
}
