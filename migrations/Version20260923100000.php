<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260923100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename category table to prestation_category';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE category TO prestation_category');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('RENAME TABLE prestation_category TO category');
    }
}
