<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220118125659 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE balu_apartment (id INT AUTO_INCREMENT NOT NULL, property_id INT DEFAULT NULL, apartment_document_id INT DEFAULT NULL, object_type_id INT DEFAULT NULL, reference_rate_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, active TINYINT(1) DEFAULT NULL, additional_cost DOUBLE PRECISION DEFAULT NULL, actual_index_stand VARCHAR(180) DEFAULT NULL, payment_mode VARCHAR(180) DEFAULT NULL, actual_index_stand_number DOUBLE PRECISION DEFAULT NULL, area DOUBLE PRECISION DEFAULT NULL, rooms VARCHAR(25) DEFAULT NULL, rent DOUBLE PRECISION DEFAULT NULL, floor VARCHAR(180) DEFAULT NULL, floor_name VARCHAR(250) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_2E284F9FB5B48B91 (public_id), INDEX IDX_2E284F9F549213EC (property_id), INDEX IDX_2E284F9F8FB58DBC (apartment_document_id), INDEX IDX_2E284F9FC5020C33 (object_type_id), INDEX IDX_2E284F9FBBE93A37 (reference_rate_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_apartment_document (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name VARCHAR(45) DEFAULT NULL, path VARCHAR(200) NOT NULL, active TINYINT(1) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_D2CC8105B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_apartment_document_mapping (id INT AUTO_INCREMENT NOT NULL, apartment_id INT DEFAULT NULL, document_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_CE30D023B5B48B91 (public_id), INDEX IDX_CE30D023176DFE85 (apartment_id), INDEX IDX_CE30D023C33F7837 (document_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_apartment_rent_history (id INT AUTO_INCREMENT NOT NULL, apartment_id INT DEFAULT NULL, reference_rate_id INT DEFAULT NULL, basis_land_index_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, rent DOUBLE PRECISION DEFAULT NULL, rent_updated TINYINT(1) NOT NULL, additional_cost DOUBLE PRECISION DEFAULT NULL, additional_cost_updated TINYINT(1) NOT NULL, reference_rate_updated TINYINT(1) NOT NULL, basis_land_index_updated TINYINT(1) NOT NULL, actual_index_stand VARCHAR(180) DEFAULT NULL, actual_index_stand_updated TINYINT(1) NOT NULL, actual_index_stand_number DOUBLE PRECISION DEFAULT NULL, actual_index_stand_number_updated TINYINT(1) NOT NULL, payment_mode VARCHAR(180) DEFAULT NULL, payment_mode_updated TINYINT(1) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_80EC4A1AB5B48B91 (public_id), INDEX IDX_80EC4A1A176DFE85 (apartment_id), INDEX IDX_80EC4A1ABBE93A37 (reference_rate_id), INDEX IDX_80EC4A1AC9E98B5A (basis_land_index_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_company_rating (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, company_id INT DEFAULT NULL, damage_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, rating INT NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_60D7667CB5B48B91 (public_id), INDEX IDX_60D7667CA76ED395 (user_id), INDEX IDX_60D7667C979B1AD6 (company_id), INDEX IDX_60D7667C6CE425B7 (damage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_damage (id INT AUTO_INCREMENT NOT NULL, status_id INT DEFAULT NULL, user_id INT DEFAULT NULL, apartment_id INT DEFAULT NULL, damage_type_id INT DEFAULT NULL, preferred_company_id INT DEFAULT NULL, assigned_company_id INT DEFAULT NULL, company_assigned_by_id INT DEFAULT NULL, parent_damage_id INT DEFAULT NULL, child_damage_id INT DEFAULT NULL, damage_owner_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, title VARCHAR(180) NOT NULL, description TEXT DEFAULT NULL, location VARCHAR(45) DEFAULT NULL, floor VARCHAR(45) DEFAULT NULL, is_device_affected TINYINT(1) DEFAULT NULL, repair TEXT DEFAULT NULL, bar_code VARCHAR(255) DEFAULT NULL, internal_reference_number VARCHAR(255) DEFAULT NULL, signature TINYINT(1) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_766A708EB5B48B91 (public_id), INDEX IDX_766A708E6BF700BD (status_id), INDEX IDX_766A708EA76ED395 (user_id), INDEX IDX_766A708E176DFE85 (apartment_id), INDEX IDX_766A708E41E13755 (damage_type_id), INDEX IDX_766A708E5A9107FF (preferred_company_id), INDEX IDX_766A708EAF3A79A7 (assigned_company_id), INDEX IDX_766A708E40A73DE5 (company_assigned_by_id), INDEX IDX_766A708E9DAF0A59 (parent_damage_id), INDEX IDX_766A708EBDD5453B (child_damage_id), INDEX IDX_766A708ECBFABC7 (damage_owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_damage_appointment (id INT AUTO_INCREMENT NOT NULL, damage_id INT DEFAULT NULL, user_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, scheduled_time DATETIME NOT NULL, status TINYINT(1) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_922D8A51B5B48B91 (public_id), INDEX IDX_922D8A516CE425B7 (damage_id), INDEX IDX_922D8A51A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_damage_comment (id INT AUTO_INCREMENT NOT NULL, damage_id INT DEFAULT NULL, status_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, comment LONGTEXT DEFAULT NULL, current_active TINYINT(1) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_9518CD81B5B48B91 (public_id), INDEX IDX_9518CD816CE425B7 (damage_id), INDEX IDX_9518CD816BF700BD (status_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_damage_defect (id INT AUTO_INCREMENT NOT NULL, damage_id INT DEFAULT NULL, user_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, description LONGTEXT DEFAULT NULL, title VARCHAR(225) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_8D72D3C6B5B48B91 (public_id), INDEX IDX_8D72D3C6A76ED395 (user_id), UNIQUE INDEX unique_entries (damage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_damage_image (id INT AUTO_INCREMENT NOT NULL, damage_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name VARCHAR(180) NOT NULL, path VARCHAR(180) NOT NULL, image_category INT DEFAULT NULL, mime_type VARCHAR(255) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_968A7E21B5B48B91 (public_id), INDEX IDX_968A7E216CE425B7 (damage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_damage_log (id INT AUTO_INCREMENT NOT NULL, status_id INT DEFAULT NULL, damage_id INT DEFAULT NULL, user_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, comment LONGTEXT DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_BD578F43B5B48B91 (public_id), INDEX IDX_BD578F436BF700BD (status_id), INDEX IDX_BD578F436CE425B7 (damage_id), INDEX IDX_BD578F43A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_damage_offer (id INT AUTO_INCREMENT NOT NULL, damage_id INT DEFAULT NULL, attachment_id INT DEFAULT NULL, company_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, description TEXT DEFAULT NULL, amount DOUBLE PRECISION DEFAULT NULL, accepted TINYINT(1) DEFAULT NULL, active TINYINT(1) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_7A61FD40B5B48B91 (public_id), INDEX IDX_7A61FD406CE425B7 (damage_id), INDEX IDX_7A61FD40464E68B (attachment_id), INDEX IDX_7A61FD40979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_damage_offer_field (id INT AUTO_INCREMENT NOT NULL, offer_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, label VARCHAR(180) DEFAULT NULL, amount DOUBLE PRECISION DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_B4CD4AA4B5B48B91 (public_id), INDEX IDX_B4CD4AA453C674EE (offer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_damage_status (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, status VARCHAR(180) NOT NULL, active TINYINT(1) DEFAULT NULL, `key` VARCHAR(180) NOT NULL, comment_required TINYINT(1) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_CCEE8E5DB5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_damage_type (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name VARCHAR(180) NOT NULL, name_de VARCHAR(180) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_883719DBB5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_document_link (id INT AUTO_INCREMENT NOT NULL, document_id INT DEFAULT NULL, user_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, document_path VARCHAR(255) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_350CE630B5B48B91 (public_id), INDEX IDX_350CE630C33F7837 (document_id), INDEX IDX_350CE630A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_favourite_company (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, favourite_company_id INT DEFAULT NULL, property_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_AEDB2C3BB5B48B91 (public_id), INDEX IDX_AEDB2C3BA76ED395 (user_id), INDEX IDX_AEDB2C3B166A8324 (favourite_company_id), INDEX IDX_AEDB2C3B549213EC (property_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_land_index (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name VARCHAR(180) DEFAULT NULL, name_de VARCHAR(180) DEFAULT NULL, sort_order INT DEFAULT NULL, active TINYINT(1) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_6E8DA431B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_mail_queue (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, mail_type VARCHAR(180) DEFAULT NULL, subject VARCHAR(180) DEFAULT NULL, to_mail VARCHAR(180) DEFAULT NULL, body_text LONGTEXT DEFAULT NULL, fail_count INT DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_47EB9013B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_object_types (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name VARCHAR(180) DEFAULT NULL, name_de VARCHAR(180) DEFAULT NULL, sort_order INT DEFAULT NULL, active TINYINT(1) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_1824E2B4B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_push_notification (id INT AUTO_INCREMENT NOT NULL, damage_id INT DEFAULT NULL, to_user_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, message TEXT DEFAULT NULL, read_message TINYINT(1) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_632998EEB5B48B91 (public_id), INDEX IDX_632998EE6CE425B7 (damage_id), INDEX IDX_632998EE29F6EE60 (to_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_reference_index (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, name VARCHAR(180) DEFAULT NULL, sort_order INT DEFAULT NULL, active TINYINT(1) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_FAEB3F75B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_stripe_event (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, event_id VARCHAR(255) NOT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_D43B5C6CB5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_subscription_rate (id INT AUTO_INCREMENT NOT NULL, subscription_plan_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, apartment_max INT DEFAULT NULL, apartment_min INT DEFAULT NULL, amount DOUBLE PRECISION DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_C1439523B5B48B91 (public_id), INDEX IDX_C14395239B8CE200 (subscription_plan_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_tenant (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, apartment_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, contract_start_date DATETIME DEFAULT NULL, contract_end_date DATETIME DEFAULT NULL, notice_period_days INT DEFAULT NULL, active TINYINT(1) NOT NULL, fixed_term_contract TINYINT(1) NOT NULL, rent DOUBLE PRECISION DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_29FBE080B5B48B91 (public_id), INDEX IDX_29FBE080A76ED395 (user_id), INDEX IDX_29FBE080176DFE85 (apartment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_user_device (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, device_id VARCHAR(255) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_E179750BB5B48B91 (public_id), INDEX IDX_E179750BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE balu_user_permissions (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, role_id INT DEFAULT NULL, permission_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted TINYINT(1) NOT NULL, is_company TINYINT(1) DEFAULT NULL, public_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', UNIQUE INDEX UNIQ_CE859983B5B48B91 (public_id), INDEX IDX_CE859983A76ED395 (user_id), INDEX IDX_CE859983D60322AC (role_id), INDEX IDX_CE859983FED90CCA (permission_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE balu_apartment ADD CONSTRAINT FK_2E284F9F549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id)');
        $this->addSql('ALTER TABLE balu_apartment ADD CONSTRAINT FK_2E284F9F8FB58DBC FOREIGN KEY (apartment_document_id) REFERENCES balu_apartment_document (id)');
        $this->addSql('ALTER TABLE balu_apartment ADD CONSTRAINT FK_2E284F9FC5020C33 FOREIGN KEY (object_type_id) REFERENCES balu_object_types (id)');
        $this->addSql('ALTER TABLE balu_apartment ADD CONSTRAINT FK_2E284F9FBBE93A37 FOREIGN KEY (reference_rate_id) REFERENCES balu_reference_index (id)');
        $this->addSql('ALTER TABLE balu_apartment_document_mapping ADD CONSTRAINT FK_CE30D023176DFE85 FOREIGN KEY (apartment_id) REFERENCES balu_apartment (id)');
        $this->addSql('ALTER TABLE balu_apartment_document_mapping ADD CONSTRAINT FK_CE30D023C33F7837 FOREIGN KEY (document_id) REFERENCES balu_apartment_document (id)');
        $this->addSql('ALTER TABLE balu_apartment_rent_history ADD CONSTRAINT FK_80EC4A1A176DFE85 FOREIGN KEY (apartment_id) REFERENCES balu_apartment (id)');
        $this->addSql('ALTER TABLE balu_apartment_rent_history ADD CONSTRAINT FK_80EC4A1ABBE93A37 FOREIGN KEY (reference_rate_id) REFERENCES balu_reference_index (id)');
        $this->addSql('ALTER TABLE balu_apartment_rent_history ADD CONSTRAINT FK_80EC4A1AC9E98B5A FOREIGN KEY (basis_land_index_id) REFERENCES balu_land_index (id)');
        $this->addSql('ALTER TABLE balu_company_rating ADD CONSTRAINT FK_60D7667CA76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_company_rating ADD CONSTRAINT FK_60D7667C979B1AD6 FOREIGN KEY (company_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_company_rating ADD CONSTRAINT FK_60D7667C6CE425B7 FOREIGN KEY (damage_id) REFERENCES balu_damage (id)');
        $this->addSql('ALTER TABLE balu_damage ADD CONSTRAINT FK_766A708E6BF700BD FOREIGN KEY (status_id) REFERENCES balu_damage_status (id)');
        $this->addSql('ALTER TABLE balu_damage ADD CONSTRAINT FK_766A708EA76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_damage ADD CONSTRAINT FK_766A708E176DFE85 FOREIGN KEY (apartment_id) REFERENCES balu_apartment (id)');
        $this->addSql('ALTER TABLE balu_damage ADD CONSTRAINT FK_766A708E41E13755 FOREIGN KEY (damage_type_id) REFERENCES balu_damage_type (id)');
        $this->addSql('ALTER TABLE balu_damage ADD CONSTRAINT FK_766A708E5A9107FF FOREIGN KEY (preferred_company_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_damage ADD CONSTRAINT FK_766A708EAF3A79A7 FOREIGN KEY (assigned_company_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_damage ADD CONSTRAINT FK_766A708E40A73DE5 FOREIGN KEY (company_assigned_by_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_damage ADD CONSTRAINT FK_766A708E9DAF0A59 FOREIGN KEY (parent_damage_id) REFERENCES balu_damage (id)');
        $this->addSql('ALTER TABLE balu_damage ADD CONSTRAINT FK_766A708EBDD5453B FOREIGN KEY (child_damage_id) REFERENCES balu_damage (id)');
        $this->addSql('ALTER TABLE balu_damage ADD CONSTRAINT FK_766A708ECBFABC7 FOREIGN KEY (damage_owner_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_damage_appointment ADD CONSTRAINT FK_922D8A516CE425B7 FOREIGN KEY (damage_id) REFERENCES balu_damage (id)');
        $this->addSql('ALTER TABLE balu_damage_appointment ADD CONSTRAINT FK_922D8A51A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_damage_comment ADD CONSTRAINT FK_9518CD816CE425B7 FOREIGN KEY (damage_id) REFERENCES balu_damage (id)');
        $this->addSql('ALTER TABLE balu_damage_comment ADD CONSTRAINT FK_9518CD816BF700BD FOREIGN KEY (status_id) REFERENCES balu_damage_status (id)');
        $this->addSql('ALTER TABLE balu_damage_defect ADD CONSTRAINT FK_8D72D3C66CE425B7 FOREIGN KEY (damage_id) REFERENCES balu_damage (id)');
        $this->addSql('ALTER TABLE balu_damage_defect ADD CONSTRAINT FK_8D72D3C6A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_damage_image ADD CONSTRAINT FK_968A7E216CE425B7 FOREIGN KEY (damage_id) REFERENCES balu_damage (id)');
        $this->addSql('ALTER TABLE balu_damage_log ADD CONSTRAINT FK_BD578F436BF700BD FOREIGN KEY (status_id) REFERENCES balu_damage_status (id)');
        $this->addSql('ALTER TABLE balu_damage_log ADD CONSTRAINT FK_BD578F436CE425B7 FOREIGN KEY (damage_id) REFERENCES balu_damage (id)');
        $this->addSql('ALTER TABLE balu_damage_log ADD CONSTRAINT FK_BD578F43A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_damage_offer ADD CONSTRAINT FK_7A61FD406CE425B7 FOREIGN KEY (damage_id) REFERENCES balu_damage (id)');
        $this->addSql('ALTER TABLE balu_damage_offer ADD CONSTRAINT FK_7A61FD40464E68B FOREIGN KEY (attachment_id) REFERENCES balu_damage_image (id)');
        $this->addSql('ALTER TABLE balu_damage_offer ADD CONSTRAINT FK_7A61FD40979B1AD6 FOREIGN KEY (company_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_damage_offer_field ADD CONSTRAINT FK_B4CD4AA453C674EE FOREIGN KEY (offer_id) REFERENCES balu_damage_offer (id)');
        $this->addSql('ALTER TABLE balu_document_link ADD CONSTRAINT FK_350CE630C33F7837 FOREIGN KEY (document_id) REFERENCES balu_property_document (id)');
        $this->addSql('ALTER TABLE balu_document_link ADD CONSTRAINT FK_350CE630A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_favourite_company ADD CONSTRAINT FK_AEDB2C3BA76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_favourite_company ADD CONSTRAINT FK_AEDB2C3B166A8324 FOREIGN KEY (favourite_company_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_favourite_company ADD CONSTRAINT FK_AEDB2C3B549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id)');
        $this->addSql('ALTER TABLE balu_push_notification ADD CONSTRAINT FK_632998EE6CE425B7 FOREIGN KEY (damage_id) REFERENCES balu_damage (id)');
        $this->addSql('ALTER TABLE balu_push_notification ADD CONSTRAINT FK_632998EE29F6EE60 FOREIGN KEY (to_user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_subscription_rate ADD CONSTRAINT FK_C14395239B8CE200 FOREIGN KEY (subscription_plan_id) REFERENCES balu_subscription_plan (id)');
        $this->addSql('ALTER TABLE balu_tenant ADD CONSTRAINT FK_29FBE080A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_tenant ADD CONSTRAINT FK_29FBE080176DFE85 FOREIGN KEY (apartment_id) REFERENCES balu_apartment (id)');
        $this->addSql('ALTER TABLE balu_user_device ADD CONSTRAINT FK_E179750BA76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_user_permissions ADD CONSTRAINT FK_CE859983A76ED395 FOREIGN KEY (user_id) REFERENCES balu_user_identity (id)');
        $this->addSql('ALTER TABLE balu_user_permissions ADD CONSTRAINT FK_CE859983D60322AC FOREIGN KEY (role_id) REFERENCES balu_role (id)');
        $this->addSql('ALTER TABLE balu_user_permissions ADD CONSTRAINT FK_CE859983FED90CCA FOREIGN KEY (permission_id) REFERENCES balu_permission (id)');
        $this->addSql('DROP TABLE balu_property_attachments');
        $this->addSql('ALTER TABLE balu_property_payment DROP FOREIGN KEY FK_7E96520C4C3A3BB');
        $this->addSql('ALTER TABLE balu_property_payment DROP FOREIGN KEY FK_7E96520C549213EC');
        $this->addSql('ALTER TABLE balu_property_payment DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE balu_property_payment ADD CONSTRAINT FK_7E96520C4C3A3BB FOREIGN KEY (payment_id) REFERENCES balu_payment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balu_property_payment ADD CONSTRAINT FK_7E96520C549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE balu_property_payment ADD PRIMARY KEY (property_id, payment_id)');
        $this->addSql('ALTER TABLE balu_subscription_plan ADD apartment_max INT DEFAULT NULL, ADD apartment_min INT DEFAULT NULL, DROP appartment_max, DROP appartment_min, CHANGE inapp_plan in_app_plan DOUBLE PRECISION DEFAULT NULL, CHANGE inapp_amount in_app_amount DOUBLE PRECISION DEFAULT \'0\'');
        $this->addSql('ALTER TABLE balu_user_identity ADD administrator_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE balu_user_identity ADD CONSTRAINT FK_2E0CFE964B09E92C FOREIGN KEY (administrator_id) REFERENCES balu_user_identity (id)');
        $this->addSql('CREATE INDEX IDX_2E0CFE964B09E92C ON balu_user_identity (administrator_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE balu_apartment_document_mapping DROP FOREIGN KEY FK_CE30D023176DFE85');
        $this->addSql('ALTER TABLE balu_apartment_rent_history DROP FOREIGN KEY FK_80EC4A1A176DFE85');
        $this->addSql('ALTER TABLE balu_damage DROP FOREIGN KEY FK_766A708E176DFE85');
        $this->addSql('ALTER TABLE balu_tenant DROP FOREIGN KEY FK_29FBE080176DFE85');
        $this->addSql('ALTER TABLE balu_apartment DROP FOREIGN KEY FK_2E284F9F8FB58DBC');
        $this->addSql('ALTER TABLE balu_apartment_document_mapping DROP FOREIGN KEY FK_CE30D023C33F7837');
        $this->addSql('ALTER TABLE balu_company_rating DROP FOREIGN KEY FK_60D7667C6CE425B7');
        $this->addSql('ALTER TABLE balu_damage DROP FOREIGN KEY FK_766A708E9DAF0A59');
        $this->addSql('ALTER TABLE balu_damage DROP FOREIGN KEY FK_766A708EBDD5453B');
        $this->addSql('ALTER TABLE balu_damage_appointment DROP FOREIGN KEY FK_922D8A516CE425B7');
        $this->addSql('ALTER TABLE balu_damage_comment DROP FOREIGN KEY FK_9518CD816CE425B7');
        $this->addSql('ALTER TABLE balu_damage_defect DROP FOREIGN KEY FK_8D72D3C66CE425B7');
        $this->addSql('ALTER TABLE balu_damage_image DROP FOREIGN KEY FK_968A7E216CE425B7');
        $this->addSql('ALTER TABLE balu_damage_log DROP FOREIGN KEY FK_BD578F436CE425B7');
        $this->addSql('ALTER TABLE balu_damage_offer DROP FOREIGN KEY FK_7A61FD406CE425B7');
        $this->addSql('ALTER TABLE balu_push_notification DROP FOREIGN KEY FK_632998EE6CE425B7');
        $this->addSql('ALTER TABLE balu_damage_offer DROP FOREIGN KEY FK_7A61FD40464E68B');
        $this->addSql('ALTER TABLE balu_damage_offer_field DROP FOREIGN KEY FK_B4CD4AA453C674EE');
        $this->addSql('ALTER TABLE balu_damage DROP FOREIGN KEY FK_766A708E6BF700BD');
        $this->addSql('ALTER TABLE balu_damage_comment DROP FOREIGN KEY FK_9518CD816BF700BD');
        $this->addSql('ALTER TABLE balu_damage_log DROP FOREIGN KEY FK_BD578F436BF700BD');
        $this->addSql('ALTER TABLE balu_damage DROP FOREIGN KEY FK_766A708E41E13755');
        $this->addSql('ALTER TABLE balu_apartment_rent_history DROP FOREIGN KEY FK_80EC4A1AC9E98B5A');
        $this->addSql('ALTER TABLE balu_apartment DROP FOREIGN KEY FK_2E284F9FC5020C33');
        $this->addSql('ALTER TABLE balu_apartment DROP FOREIGN KEY FK_2E284F9FBBE93A37');
        $this->addSql('ALTER TABLE balu_apartment_rent_history DROP FOREIGN KEY FK_80EC4A1ABBE93A37');
        $this->addSql('CREATE TABLE balu_property_attachments (property_id INT NOT NULL, document_id INT NOT NULL, INDEX IDX_888416D9549213EC (property_id), INDEX IDX_888416D9C33F7837 (document_id), PRIMARY KEY(property_id, document_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE balu_property_attachments ADD CONSTRAINT FK_888416D9549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_property_attachments ADD CONSTRAINT FK_888416D9C33F7837 FOREIGN KEY (document_id) REFERENCES balu_property_document (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP TABLE balu_apartment');
        $this->addSql('DROP TABLE balu_apartment_document');
        $this->addSql('DROP TABLE balu_apartment_document_mapping');
        $this->addSql('DROP TABLE balu_apartment_rent_history');
        $this->addSql('DROP TABLE balu_company_rating');
        $this->addSql('DROP TABLE balu_damage');
        $this->addSql('DROP TABLE balu_damage_appointment');
        $this->addSql('DROP TABLE balu_damage_comment');
        $this->addSql('DROP TABLE balu_damage_defect');
        $this->addSql('DROP TABLE balu_damage_image');
        $this->addSql('DROP TABLE balu_damage_log');
        $this->addSql('DROP TABLE balu_damage_offer');
        $this->addSql('DROP TABLE balu_damage_offer_field');
        $this->addSql('DROP TABLE balu_damage_status');
        $this->addSql('DROP TABLE balu_damage_type');
        $this->addSql('DROP TABLE balu_document_link');
        $this->addSql('DROP TABLE balu_favourite_company');
        $this->addSql('DROP TABLE balu_land_index');
        $this->addSql('DROP TABLE balu_mail_queue');
        $this->addSql('DROP TABLE balu_object_types');
        $this->addSql('DROP TABLE balu_push_notification');
        $this->addSql('DROP TABLE balu_reference_index');
        $this->addSql('DROP TABLE balu_stripe_event');
        $this->addSql('DROP TABLE balu_subscription_rate');
        $this->addSql('DROP TABLE balu_tenant');
        $this->addSql('DROP TABLE balu_user_device');
        $this->addSql('DROP TABLE balu_user_permissions');
        $this->addSql('ALTER TABLE balu_property_payment DROP FOREIGN KEY FK_7E96520C549213EC');
        $this->addSql('ALTER TABLE balu_property_payment DROP FOREIGN KEY FK_7E96520C4C3A3BB');
        $this->addSql('ALTER TABLE balu_property_payment DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE balu_property_payment ADD CONSTRAINT FK_7E96520C549213EC FOREIGN KEY (property_id) REFERENCES balu_property (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_property_payment ADD CONSTRAINT FK_7E96520C4C3A3BB FOREIGN KEY (payment_id) REFERENCES balu_payment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE balu_property_payment ADD PRIMARY KEY (payment_id, property_id)');
        $this->addSql('ALTER TABLE balu_subscription_plan ADD appartment_max INT DEFAULT NULL, ADD appartment_min INT DEFAULT NULL, DROP apartment_max, DROP apartment_min, CHANGE in_app_plan inapp_plan DOUBLE PRECISION DEFAULT NULL, CHANGE in_app_amount inapp_amount DOUBLE PRECISION DEFAULT \'0\'');
        $this->addSql('ALTER TABLE balu_user_identity DROP FOREIGN KEY FK_2E0CFE964B09E92C');
        $this->addSql('DROP INDEX IDX_2E0CFE964B09E92C ON balu_user_identity');
        $this->addSql('ALTER TABLE balu_user_identity DROP administrator_id');
    }
}
