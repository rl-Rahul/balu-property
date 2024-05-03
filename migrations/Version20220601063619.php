<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220601063619 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8542B467A76ED39534E604B3 ON balu_favourite_admin (user_id, favourite_admin_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AEDB2C3BA76ED395166A8324 ON balu_favourite_company (user_id, favourite_company_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_764749DCA76ED395725E48EB ON balu_favourite_individual (user_id, favourite_individual_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8542B467A76ED39534E604B3 ON balu_favourite_admin');
        $this->addSql('DROP INDEX UNIQ_AEDB2C3BA76ED395166A8324 ON balu_favourite_company');
        $this->addSql('DROP INDEX UNIQ_764749DCA76ED395725E48EB ON balu_favourite_individual');
    }
}
