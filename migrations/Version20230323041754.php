<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230323041754 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_reset_object ADD requested_by_id INT NOT NULL');
        $this->addSql('ALTER TABLE balu_reset_object ADD CONSTRAINT FK_B8722A134DA1E751 FOREIGN KEY (requested_by_id) REFERENCES balu_user_identity (id)');
        $this->addSql('CREATE INDEX IDX_B8722A134DA1E751 ON balu_reset_object (requested_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_reset_object DROP FOREIGN KEY FK_B8722A134DA1E751');
        $this->addSql('DROP INDEX IDX_B8722A134DA1E751 ON balu_reset_object');
        $this->addSql('ALTER TABLE balu_reset_object DROP requested_by_id');
    }
}
