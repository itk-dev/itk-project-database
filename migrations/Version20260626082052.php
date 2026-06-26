<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260626082052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(64) DEFAULT NULL, department VARCHAR(255) DEFAULT NULL, created_by_id BINARY(16) DEFAULT NULL, modified_by_id BINARY(16) DEFAULT NULL, INDEX IDX_4C62E638B03A8386 (created_by_id), INDEX IDX_4C62E63899049ECE (modified_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE initiative (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, title VARCHAR(255) NOT NULL, category VARCHAR(32) DEFAULT NULL, description LONGTEXT DEFAULT NULL, initiative_type VARCHAR(32) DEFAULT NULL, status VARCHAR(32) DEFAULT NULL, status_additional LONGTEXT DEFAULT NULL, organizational_anchoring VARCHAR(64) DEFAULT NULL, endorsement TINYINT NOT NULL, endorsement_author VARCHAR(32) DEFAULT NULL, budget INT DEFAULT NULL, funding JSON NOT NULL, time_period_start DATE DEFAULT NULL, time_period_end DATE DEFAULT NULL, links JSON NOT NULL, created_by_id BINARY(16) DEFAULT NULL, modified_by_id BINARY(16) DEFAULT NULL, INDEX IDX_E115DEFEB03A8386 (created_by_id), INDEX IDX_E115DEFE99049ECE (modified_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE initiative_strategy (initiative_id BINARY(16) NOT NULL, term_id BINARY(16) NOT NULL, INDEX IDX_9FDB07E5AB7D9771 (initiative_id), INDEX IDX_9FDB07E5E2C35FC (term_id), PRIMARY KEY (initiative_id, term_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE initiative_contact (initiative_id BINARY(16) NOT NULL, contact_id BINARY(16) NOT NULL, INDEX IDX_6F980462AB7D9771 (initiative_id), INDEX IDX_6F980462E7A1254A (contact_id), PRIMARY KEY (initiative_id, contact_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE initiative_stakeholder (initiative_id BINARY(16) NOT NULL, term_id BINARY(16) NOT NULL, INDEX IDX_C97AB0E7AB7D9771 (initiative_id), INDEX IDX_C97AB0E7E2C35FC (term_id), PRIMARY KEY (initiative_id, term_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE initiative_tag (initiative_id BINARY(16) NOT NULL, term_id BINARY(16) NOT NULL, INDEX IDX_4FF4E32DAB7D9771 (initiative_id), INDEX IDX_4FF4E32DE2C35FC (term_id), PRIMARY KEY (initiative_id, term_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE initiative_attachment (id BINARY(16) NOT NULL, file_name VARCHAR(255) DEFAULT NULL, original_name VARCHAR(255) DEFAULT NULL, mime_type VARCHAR(255) DEFAULT NULL, size INT DEFAULT NULL, updated_at DATETIME DEFAULT NULL, initiative_id BINARY(16) NOT NULL, INDEX IDX_2954F892AB7D9771 (initiative_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE initiative_image (id BINARY(16) NOT NULL, image_name VARCHAR(255) DEFAULT NULL, original_name VARCHAR(255) DEFAULT NULL, mime_type VARCHAR(255) DEFAULT NULL, size INT DEFAULT NULL, alt VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, initiative_id BINARY(16) NOT NULL, INDEX IDX_99BF4CA8AB7D9771 (initiative_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE term (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, name VARCHAR(255) NOT NULL, vocabulary VARCHAR(32) NOT NULL, created_by_id BINARY(16) DEFAULT NULL, modified_by_id BINARY(16) DEFAULT NULL, INDEX IDX_A50FE78DB03A8386 (created_by_id), INDEX IDX_A50FE78D99049ECE (modified_by_id), UNIQUE INDEX uniq_term_name_vocabulary (name, vocabulary), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, email VARCHAR(180) NOT NULL, name VARCHAR(255) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, created_by_id BINARY(16) DEFAULT NULL, modified_by_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), INDEX IDX_8D93D649B03A8386 (created_by_id), INDEX IDX_8D93D64999049ECE (modified_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E638B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E63899049ECE FOREIGN KEY (modified_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE initiative ADD CONSTRAINT FK_E115DEFEB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE initiative ADD CONSTRAINT FK_E115DEFE99049ECE FOREIGN KEY (modified_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE initiative_strategy ADD CONSTRAINT FK_9FDB07E5AB7D9771 FOREIGN KEY (initiative_id) REFERENCES initiative (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE initiative_strategy ADD CONSTRAINT FK_9FDB07E5E2C35FC FOREIGN KEY (term_id) REFERENCES term (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE initiative_contact ADD CONSTRAINT FK_6F980462AB7D9771 FOREIGN KEY (initiative_id) REFERENCES initiative (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE initiative_contact ADD CONSTRAINT FK_6F980462E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE initiative_stakeholder ADD CONSTRAINT FK_C97AB0E7AB7D9771 FOREIGN KEY (initiative_id) REFERENCES initiative (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE initiative_stakeholder ADD CONSTRAINT FK_C97AB0E7E2C35FC FOREIGN KEY (term_id) REFERENCES term (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE initiative_tag ADD CONSTRAINT FK_4FF4E32DAB7D9771 FOREIGN KEY (initiative_id) REFERENCES initiative (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE initiative_tag ADD CONSTRAINT FK_4FF4E32DE2C35FC FOREIGN KEY (term_id) REFERENCES term (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE initiative_attachment ADD CONSTRAINT FK_2954F892AB7D9771 FOREIGN KEY (initiative_id) REFERENCES initiative (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE initiative_image ADD CONSTRAINT FK_99BF4CA8AB7D9771 FOREIGN KEY (initiative_id) REFERENCES initiative (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE term ADD CONSTRAINT FK_A50FE78DB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE term ADD CONSTRAINT FK_A50FE78D99049ECE FOREIGN KEY (modified_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D64999049ECE FOREIGN KEY (modified_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E638B03A8386');
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E63899049ECE');
        $this->addSql('ALTER TABLE initiative DROP FOREIGN KEY FK_E115DEFEB03A8386');
        $this->addSql('ALTER TABLE initiative DROP FOREIGN KEY FK_E115DEFE99049ECE');
        $this->addSql('ALTER TABLE initiative_strategy DROP FOREIGN KEY FK_9FDB07E5AB7D9771');
        $this->addSql('ALTER TABLE initiative_strategy DROP FOREIGN KEY FK_9FDB07E5E2C35FC');
        $this->addSql('ALTER TABLE initiative_contact DROP FOREIGN KEY FK_6F980462AB7D9771');
        $this->addSql('ALTER TABLE initiative_contact DROP FOREIGN KEY FK_6F980462E7A1254A');
        $this->addSql('ALTER TABLE initiative_stakeholder DROP FOREIGN KEY FK_C97AB0E7AB7D9771');
        $this->addSql('ALTER TABLE initiative_stakeholder DROP FOREIGN KEY FK_C97AB0E7E2C35FC');
        $this->addSql('ALTER TABLE initiative_tag DROP FOREIGN KEY FK_4FF4E32DAB7D9771');
        $this->addSql('ALTER TABLE initiative_tag DROP FOREIGN KEY FK_4FF4E32DE2C35FC');
        $this->addSql('ALTER TABLE initiative_attachment DROP FOREIGN KEY FK_2954F892AB7D9771');
        $this->addSql('ALTER TABLE initiative_image DROP FOREIGN KEY FK_99BF4CA8AB7D9771');
        $this->addSql('ALTER TABLE term DROP FOREIGN KEY FK_A50FE78DB03A8386');
        $this->addSql('ALTER TABLE term DROP FOREIGN KEY FK_A50FE78D99049ECE');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649B03A8386');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64999049ECE');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE initiative');
        $this->addSql('DROP TABLE initiative_strategy');
        $this->addSql('DROP TABLE initiative_contact');
        $this->addSql('DROP TABLE initiative_stakeholder');
        $this->addSql('DROP TABLE initiative_tag');
        $this->addSql('DROP TABLE initiative_attachment');
        $this->addSql('DROP TABLE initiative_image');
        $this->addSql('DROP TABLE term');
        $this->addSql('DROP TABLE `user`');
    }
}
