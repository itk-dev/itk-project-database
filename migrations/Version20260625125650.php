<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260625125650 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE department (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_CD1DE18A5E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE initiative ADD organizational_anchoring_id INT DEFAULT NULL, DROP organizational_anchoring');
        $this->addSql('ALTER TABLE initiative ADD CONSTRAINT FK_E115DEFEA0A5E935 FOREIGN KEY (organizational_anchoring_id) REFERENCES department (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_E115DEFEA0A5E935 ON initiative (organizational_anchoring_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE department');
        $this->addSql('ALTER TABLE initiative DROP FOREIGN KEY FK_E115DEFEA0A5E935');
        $this->addSql('DROP INDEX IDX_E115DEFEA0A5E935 ON initiative');
        $this->addSql('ALTER TABLE initiative ADD organizational_anchoring VARCHAR(64) DEFAULT NULL, DROP organizational_anchoring_id');
    }
}
