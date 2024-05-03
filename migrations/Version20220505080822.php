<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220505080822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_favourite_admin (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, favourite_admin_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_8542B467B5B48B91 (public_id), INDEX IDX_8542B467A76ED395 (user_id), INDEX IDX_8542B46734E604B3 (favourite_admin_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_favourite_individual (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, favourite_individual_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_764749DCB5B48B91 (public_id), INDEX IDX_764749DCA76ED395 (user_id), INDEX IDX_764749DC725E48EB (favourite_individual_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_favourite_admin ADD CONSTRAINT FK_8542B467A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_favourite_admin ADD CONSTRAINT FK_8542B46734E604B3 FOREIGN KEY (favourite_admin_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_favourite_individual ADD CONSTRAINT FK_764749DCA76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_favourite_individual ADD CONSTRAINT FK_764749DC725E48EB FOREIGN KEY (favourite_individual_id) REFERENCES balu_user_identity (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_favourite_admin');
        $this->addSql('DROP TABLE balu_favourite_individual');
    }
}
