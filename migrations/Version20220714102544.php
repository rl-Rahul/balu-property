<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220714102544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    { 
        $this->addSql('CREATE TABLE balu_damage_user_identity (damage_id INT NOT NULL, user_identity_id INT NOT NULL, INDEX IDX_3A697D56CE425B7 (damage_id), INDEX IDX_3A697D556251D3D (user_identity_id), PRIMARY KEY(damage_id, user_identity_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_damage_user_identity ADD CONSTRAINT FK_3A697D56CE425B7 FOREIGN KEY (damage_id) REFERENCES balu_damage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balu_damage_user_identity ADD CONSTRAINT FK_3A697D556251D3D FOREIGN KEY (user_identity_id) REFERENCES balu_user_identity (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    { 
        $this->addSql('DROP TABLE balu_damage_user_identity');
    }
}
