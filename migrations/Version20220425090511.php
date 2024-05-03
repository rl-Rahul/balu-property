<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220425090511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_apartment ADD CONSTRAINT FK_2E284F9FB03A8386 FOREIGN KEY (created_by_id) REFERENCES balu_user_identity (id)');
        $this->addSql('CREATE INDEX IDX_2E284F9FB03A8386 ON balu_apartment (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment CHANGE created_by_id ccreated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_apartment ADD CONSTRAINT FK_2E284F9F9E491631 FOREIGN KEY (ccreated_by_id) REFERENCES balu_user_identity (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_2E284F9F9E491631 ON balu_apartment (ccreated_by_id)');
    }
}
