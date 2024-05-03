<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240312071345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_directory ADD property_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_directory ADD CONSTRAINT FK_252E6311549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id)');
        $this->addSql('CREATE INDEX IDX_252E6311549213EC ON balu_directory (property_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_directory DROP FOREIGN KEY FK_252E6311549213EC');
        $this->addSql('DROP INDEX IDX_252E6311549213EC ON balu_directory');
        $this->addSql('ALTER TABLE balu_directory DROP property_id');
    }
}
