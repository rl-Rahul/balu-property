<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221116082730 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_company_user_permission (permission_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_51500FBDFED90CCA (permission_id), INDEX IDX_51500FBDA76ED395 (user_id), PRIMARY KEY(permission_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_company_user_permission ADD CONSTRAINT FK_51500FBDFED90CCA FOREIGN KEY (permission_id) REFERENCES balu_permission (id)');
        $this->addSql('ALTER TABLE balu_company_user_permission ADD CONSTRAINT FK_51500FBDA76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balu_company_user_permission');
    }
}
