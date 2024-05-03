<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221122101810 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_push_notification ADD role_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_push_notification ADD CONSTRAINT FK_632998EED60322AC FOREIGN KEY (role_id) REFERENCES balu_role (id)');
        $this->addSql('CREATE INDEX IDX_632998EED60322AC ON balu_push_notification (role_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_push_notification DROP FOREIGN KEY FK_632998EED60322AC');
        $this->addSql('DROP INDEX IDX_632998EED60322AC ON balu_push_notification');
        $this->addSql('ALTER TABLE balu_push_notification DROP role_id');
    }
}
