<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230905132526 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage_log ADD offer_id INT DEFAULT NULL, ADD request_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_damage_log ADD CONSTRAINT FK_BD578F4353C674EE FOREIGN KEY (offer_id) REFERENCES balu_damage_offer (id)');
        $this->addSql('ALTER TABLE balu_damage_log ADD CONSTRAINT FK_BD578F43427EB8A5 FOREIGN KEY (request_id) REFERENCES balu_damage_request (id)');
        $this->addSql('CREATE INDEX IDX_BD578F4353C674EE ON balu_damage_log (offer_id)');
        $this->addSql('CREATE INDEX IDX_BD578F43427EB8A5 ON balu_damage_log (request_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_damage_log DROP FOREIGN KEY FK_BD578F4353C674EE');
        $this->addSql('ALTER TABLE balu_damage_log DROP FOREIGN KEY FK_BD578F43427EB8A5');
        $this->addSql('DROP INDEX IDX_BD578F4353C674EE ON balu_damage_log');
        $this->addSql('DROP INDEX IDX_BD578F43427EB8A5 ON balu_damage_log');
        $this->addSql('ALTER TABLE balu_damage_log DROP offer_id, DROP request_id');
    }
}
