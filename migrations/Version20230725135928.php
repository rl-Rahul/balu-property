<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230725135928 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_property_role_invitation (id INT AUTO_INCREMENT NOT NULL, property_id INT NOT NULL, invitee_id INT DEFAULT NULL, invitor_id INT DEFAULT NULL, role_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, invitation_accepted_date DATETIME DEFAULT NULL, invitation_rejected_date DATETIME DEFAULT NULL, reason TEXT DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_7D10A238B5B48B91 (public_id), INDEX IDX_7D10A238549213EC (property_id), INDEX IDX_7D10A2387A512022 (invitee_id), INDEX IDX_7D10A238FD2F57A5 (invitor_id), INDEX IDX_7D10A238D60322AC (role_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_property_role_invitation ADD CONSTRAINT FK_7D10A238549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id)');
        $this->addSql('ALTER TABLE balu_property_role_invitation ADD CONSTRAINT FK_7D10A2387A512022 FOREIGN KEY (invitee_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_property_role_invitation ADD CONSTRAINT FK_7D10A238FD2F57A5 FOREIGN KEY (invitor_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_property_role_invitation ADD CONSTRAINT FK_7D10A238D60322AC FOREIGN KEY (role_id) REFERENCES balu_role (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_property_role_invitation DROP FOREIGN KEY FK_7D10A238549213EC');
        $this->addSql('ALTER TABLE balu_property_role_invitation DROP FOREIGN KEY FK_7D10A2387A512022');
        $this->addSql('ALTER TABLE balu_property_role_invitation DROP FOREIGN KEY FK_7D10A238FD2F57A5');
        $this->addSql('ALTER TABLE balu_property_role_invitation DROP FOREIGN KEY FK_7D10A238D60322AC');
        $this->addSql('DROP TABLE balu_property_role_invitation');
    }
}
