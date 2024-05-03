<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220123173142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_role_permission DROP FOREIGN KEY FK_C344C0E2D60322AC');
        $this->addSql('ALTER TABLE balu_role_permission DROP FOREIGN KEY FK_C344C0E2FED90CCA');
        $this->addSql('ALTER TABLE balu_role_permission ADD CONSTRAINT FK_C344C0E2D60322AC FOREIGN KEY (role_id) REFERENCES balu_role (id)');
        $this->addSql('ALTER TABLE balu_role_permission ADD CONSTRAINT FK_C344C0E2FED90CCA FOREIGN KEY (permission_id) REFERENCES balu_permission (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_role_permission DROP FOREIGN KEY FK_C344C0E2D60322AC');
        $this->addSql('ALTER TABLE balu_role_permission DROP FOREIGN KEY FK_C344C0E2FED90CCA');
        $this->addSql('ALTER TABLE balu_role_permission ADD CONSTRAINT FK_C344C0E2D60322AC FOREIGN KEY (role_id) REFERENCES balu_role (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balu_role_permission ADD CONSTRAINT FK_C344C0E2FED90CCA FOREIGN KEY (permission_id) REFERENCES balu_permission (id) ON UPDATE NO ACTION ON DELETE CASCADE');
    }
}
