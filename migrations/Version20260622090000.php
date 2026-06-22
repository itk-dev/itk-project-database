<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add Blameable created_by/modified_by relations to initiative.
 */
final class Version20260622090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Blameable created_by/modified_by relations to initiative';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE initiative ADD created_by_id INT DEFAULT NULL, ADD modified_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE initiative ADD CONSTRAINT FK_E115DEFEB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE initiative ADD CONSTRAINT FK_E115DEFE99049ECE FOREIGN KEY (modified_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_E115DEFEB03A8386 ON initiative (created_by_id)');
        $this->addSql('CREATE INDEX IDX_E115DEFE99049ECE ON initiative (modified_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE initiative DROP FOREIGN KEY FK_E115DEFEB03A8386');
        $this->addSql('ALTER TABLE initiative DROP FOREIGN KEY FK_E115DEFE99049ECE');
        $this->addSql('DROP INDEX IDX_E115DEFEB03A8386 ON initiative');
        $this->addSql('DROP INDEX IDX_E115DEFE99049ECE ON initiative');
        $this->addSql('ALTER TABLE initiative DROP created_by_id, DROP modified_by_id');
    }
}
