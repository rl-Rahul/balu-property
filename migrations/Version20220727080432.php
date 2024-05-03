<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
 
final class Version20220727080432 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    { 
        $this->addSql('CREATE TABLE balu_damage_read (damage_id INT NOT NULL, user_identity_id INT NOT NULL, INDEX IDX_9CBE0F956CE425B7 (damage_id), INDEX IDX_9CBE0F9556251D3D (user_identity_id), PRIMARY KEY(damage_id, user_identity_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_message_read (message_id INT NOT NULL, user_identity_id INT NOT NULL, INDEX IDX_F4F5E5D8537A1329 (message_id), INDEX IDX_F4F5E5D856251D3D (user_identity_id), PRIMARY KEY(message_id, user_identity_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_damage_read ADD CONSTRAINT FK_9CBE0F956CE425B7 FOREIGN KEY (damage_id) REFERENCES balu_damage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balu_damage_read ADD CONSTRAINT FK_9CBE0F9556251D3D FOREIGN KEY (user_identity_id) REFERENCES balu_user_identity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balu_message_read ADD CONSTRAINT FK_F4F5E5D8537A1329 FOREIGN KEY (message_id) REFERENCES balu_message (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balu_message_read ADD CONSTRAINT FK_F4F5E5D856251D3D FOREIGN KEY (user_identity_id) REFERENCES balu_user_identity (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    { 
        $this->addSql('DROP TABLE balu_damage_read');
        $this->addSql('DROP TABLE balu_message_read');
    }
}
