<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220714040616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_message_user_identity (message_id INT NOT NULL, user_identity_id INT NOT NULL, INDEX IDX_9CC0E698537A1329 (message_id), INDEX IDX_9CC0E69856251D3D (user_identity_id), PRIMARY KEY(message_id, user_identity_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_message_user_identity ADD CONSTRAINT FK_9CC0E698537A1329 FOREIGN KEY (message_id) REFERENCES balu_message (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balu_message_user_identity ADD CONSTRAINT FK_9CC0E69856251D3D FOREIGN KEY (user_identity_id) REFERENCES balu_user_identity (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
         $this->addSql('DROP TABLE balu_message_user_identity');
    }
}
