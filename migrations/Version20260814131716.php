<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814131716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the topic column to initiative.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE initiative ADD topic LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE initiative DROP topic');
    }
}
