<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260923130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add notes column to company_prestation table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE company_prestation ADD notes TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE company_prestation DROP notes');
    }
}
