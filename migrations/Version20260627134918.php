<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260627134918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE area (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, name VARCHAR(255) NOT NULL, created_by_id BINARY(16) DEFAULT NULL, modified_by_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_D7943D685E237E06 (name), INDEX IDX_D7943D68B03A8386 (created_by_id), INDEX IDX_D7943D6899049ECE (modified_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE area ADD CONSTRAINT FK_D7943D68B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE area ADD CONSTRAINT FK_D7943D6899049ECE FOREIGN KEY (modified_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE initiative ADD area_id BINARY(16) DEFAULT NULL, DROP category');
        $this->addSql('ALTER TABLE initiative ADD CONSTRAINT FK_E115DEFEBD0F409C FOREIGN KEY (area_id) REFERENCES area (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_E115DEFEBD0F409C ON initiative (area_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE area DROP FOREIGN KEY FK_D7943D68B03A8386');
        $this->addSql('ALTER TABLE area DROP FOREIGN KEY FK_D7943D6899049ECE');
        $this->addSql('DROP TABLE area');
        $this->addSql('ALTER TABLE initiative DROP FOREIGN KEY FK_E115DEFEBD0F409C');
        $this->addSql('DROP INDEX IDX_E115DEFEBD0F409C ON initiative');
        $this->addSql('ALTER TABLE initiative ADD category VARCHAR(32) DEFAULT NULL, DROP area_id');
    }
}
