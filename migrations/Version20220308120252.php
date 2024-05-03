<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220308120252 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_object_amenity_measure (id INT AUTO_INCREMENT NOT NULL, object_id INT NOT NULL, amenity_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, value DOUBLE PRECISION NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_F78D9C3BB5B48B91 (public_id), INDEX IDX_F78D9C3B232D562B (object_id), INDEX IDX_F78D9C3B9F9F1305 (amenity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_object_amenity_measure ADD CONSTRAINT FK_F78D9C3B232D562B FOREIGN KEY (object_id) REFERENCES balu_apartment (id)');
        $this->addSql('ALTER TABLE balu_object_amenity_measure ADD CONSTRAINT FK_F78D9C3B9F9F1305 FOREIGN KEY (amenity_id) REFERENCES balu_amenity (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_object_amenity_measure');
    }
}
