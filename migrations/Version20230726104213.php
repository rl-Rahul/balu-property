<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230726104213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_push_notification ADD property_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_push_notification ADD CONSTRAINT FK_632998EE549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id)');
        $this->addSql('CREATE INDEX IDX_632998EE549213EC ON balu_push_notification (property_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_push_notification DROP FOREIGN KEY FK_632998EE549213EC');
        $this->addSql('DROP INDEX IDX_632998EE549213EC ON balu_push_notification');
        $this->addSql('ALTER TABLE balu_push_notification DROP property_id');
    }
}
