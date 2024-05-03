<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221201061110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_payment ADD role_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_payment ADD CONSTRAINT FK_234BA57DD60322AC FOREIGN KEY (role_id) REFERENCES balu_role (id)');
        $this->addSql('CREATE INDEX IDX_234BA57DD60322AC ON balu_payment (role_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_payment DROP FOREIGN KEY FK_234BA57DD60322AC');
        $this->addSql('DROP INDEX IDX_234BA57DD60322AC ON balu_payment');
        $this->addSql('ALTER TABLE balu_payment DROP role_id');
    }
}
