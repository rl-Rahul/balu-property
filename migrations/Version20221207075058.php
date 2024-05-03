<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221207075058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_property_payment');
        $this->addSql('ALTER TABLE balu_payment ADD property_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_payment ADD CONSTRAINT FK_234BA57D549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id)');
        $this->addSql('CREATE INDEX IDX_234BA57D549213EC ON balu_payment (property_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_property_payment (payment_id INT NOT NULL, property_id INT NOT NULL, INDEX IDX_7E96520C4C3A3BB (payment_id), INDEX IDX_7E96520C549213EC (property_id), PRIMARY KEY(property_id, payment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE balu_property_payment ADD CONSTRAINT FK_7E96520C4C3A3BB FOREIGN KEY (payment_id) REFERENCES balu_payment (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balu_property_payment ADD CONSTRAINT FK_7E96520C549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balu_payment DROP FOREIGN KEY FK_234BA57D549213EC');
        $this->addSql('DROP INDEX IDX_234BA57D549213EC ON balu_payment');
        $this->addSql('ALTER TABLE balu_payment DROP property_id');
    }
}
