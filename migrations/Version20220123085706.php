<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220123085706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_company_category (user_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_54A890D5A76ED395 (user_id), INDEX IDX_54A890D512469DE2 (category_id), PRIMARY KEY(user_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_company_category ADD CONSTRAINT FK_54A890D5A76ED395 FOREIGN KEY (user_id) REFERENCES balu_category (id)');
        $this->addSql('ALTER TABLE balu_company_category ADD CONSTRAINT FK_54A890D512469DE2 FOREIGN KEY (category_id) REFERENCES balu_user_identity (id)');
        $this->addSql('DROP TABLE balu_category_user_identity');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_category_user_identity (category_id INT NOT NULL, user_identity_id INT NOT NULL, INDEX IDX_A89FB9F312469DE2 (category_id), INDEX IDX_A89FB9F356251D3D (user_identity_id), PRIMARY KEY(category_id, user_identity_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE balu_category_user_identity ADD CONSTRAINT FK_A89FB9F312469DE2 FOREIGN KEY (category_id) REFERENCES balu_category (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balu_category_user_identity ADD CONSTRAINT FK_A89FB9F356251D3D FOREIGN KEY (user_identity_id) REFERENCES balu_user_identity (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('DROP TABLE balu_company_category');
    }
}
