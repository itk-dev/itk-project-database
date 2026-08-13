<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260813090155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE initiative_partner (initiative_id BINARY(16) NOT NULL, partner_id BINARY(16) NOT NULL, INDEX IDX_12D1DC4CAB7D9771 (initiative_id), INDEX IDX_12D1DC4C9393F8FE (partner_id), PRIMARY KEY (initiative_id, partner_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE partner (id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, created_by_id BINARY(16) DEFAULT NULL, modified_by_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_312B3E165E237E06 (name), INDEX IDX_312B3E16B03A8386 (created_by_id), INDEX IDX_312B3E1699049ECE (modified_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE initiative_partner ADD CONSTRAINT FK_12D1DC4CAB7D9771 FOREIGN KEY (initiative_id) REFERENCES initiative (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE initiative_partner ADD CONSTRAINT FK_12D1DC4C9393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE partner ADD CONSTRAINT FK_312B3E16B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE partner ADD CONSTRAINT FK_312B3E1699049ECE FOREIGN KEY (modified_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE initiative_partner DROP FOREIGN KEY FK_12D1DC4CAB7D9771');
        $this->addSql('ALTER TABLE initiative_partner DROP FOREIGN KEY FK_12D1DC4C9393F8FE');
        $this->addSql('ALTER TABLE partner DROP FOREIGN KEY FK_312B3E16B03A8386');
        $this->addSql('ALTER TABLE partner DROP FOREIGN KEY FK_312B3E1699049ECE');
        $this->addSql('DROP TABLE initiative_partner');
        $this->addSql('DROP TABLE partner');
    }
}
