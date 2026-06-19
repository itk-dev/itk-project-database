<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260618122325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE initiative_attachment (id INT AUTO_INCREMENT NOT NULL, file_name VARCHAR(255) DEFAULT NULL, original_name VARCHAR(255) DEFAULT NULL, mime_type VARCHAR(255) DEFAULT NULL, size INT DEFAULT NULL, updated_at DATETIME DEFAULT NULL, initiative_id INT NOT NULL, INDEX IDX_2954F892AB7D9771 (initiative_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE initiative_image (id INT AUTO_INCREMENT NOT NULL, image_name VARCHAR(255) DEFAULT NULL, original_name VARCHAR(255) DEFAULT NULL, mime_type VARCHAR(255) DEFAULT NULL, size INT DEFAULT NULL, alt VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, initiative_id INT NOT NULL, INDEX IDX_99BF4CA8AB7D9771 (initiative_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE initiative_attachment ADD CONSTRAINT FK_2954F892AB7D9771 FOREIGN KEY (initiative_id) REFERENCES initiative (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE initiative_image ADD CONSTRAINT FK_99BF4CA8AB7D9771 FOREIGN KEY (initiative_id) REFERENCES initiative (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE initiative_attachment DROP FOREIGN KEY FK_2954F892AB7D9771');
        $this->addSql('ALTER TABLE initiative_image DROP FOREIGN KEY FK_99BF4CA8AB7D9771');
        $this->addSql('DROP TABLE initiative_attachment');
        $this->addSql('DROP TABLE initiative_image');
    }
}
