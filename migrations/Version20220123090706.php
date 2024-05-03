<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220123090706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_company_category DROP FOREIGN KEY FK_54A890D512469DE2');
        $this->addSql('ALTER TABLE balu_company_category DROP FOREIGN KEY FK_54A890D5A76ED395');
        $this->addSql('ALTER TABLE balu_company_category DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE balu_company_category ADD CONSTRAINT FK_54A890D512469DE2 FOREIGN KEY (category_id) REFERENCES balu_category (id)');
        $this->addSql('ALTER TABLE balu_company_category ADD CONSTRAINT FK_54A890D5A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_company_category ADD PRIMARY KEY (category_id, user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_company_category DROP FOREIGN KEY FK_54A890D512469DE2');
        $this->addSql('ALTER TABLE balu_company_category DROP FOREIGN KEY FK_54A890D5A76ED395');
        $this->addSql('ALTER TABLE balu_company_category DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE balu_company_category ADD CONSTRAINT FK_54A890D512469DE2 FOREIGN KEY (category_id) REFERENCES balu_user_identity (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_company_category ADD CONSTRAINT FK_54A890D5A76ED395 FOREIGN KEY (user_id) REFERENCES balu_category (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_company_category ADD PRIMARY KEY (user_id, category_id)');
    }
}
