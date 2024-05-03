<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220627092633 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_object_contracts ADD terminated_by_id INT DEFAULT NULL, ADD notice_receipt_date DATETIME DEFAULT NULL, ADD termination_date DATETIME DEFAULT NULL, CHANGE owner_vote owner_vote TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_object_contracts ADD CONSTRAINT FK_9C0A8D86573F49 FOREIGN KEY (terminated_by_id) REFERENCES balu_user_identity (id)');
        $this->addSql('CREATE INDEX IDX_9C0A8D86573F49 ON balu_object_contracts (terminated_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_object_contracts DROP notice_receipt_date, DROP termination_date, CHANGE owner_vote owner_vote TINYINT(1) DEFAULT \'0\'');
        $this->addSql('ALTER TABLE balu_object_contracts DROP FOREIGN KEY FK_9C0A8D86573F49');
        $this->addSql('DROP INDEX IDX_9C0A8D86573F49 ON balu_object_contracts');
        $this->addSql('ALTER TABLE balu_object_contracts DROP terminated_by_id, DROP notice_receipt_date');
    }
}
