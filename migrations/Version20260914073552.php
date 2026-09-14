<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260914073552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace company.email with a unique company.slug column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE company ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE company SET slug = LOWER(REPLACE(REPLACE(TRIM(name), \' \', \'-\'), \'_\', \'-\'))');
        $this->addSql('ALTER TABLE company CHANGE slug slug VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX uniq_company_email ON company');
        $this->addSql('CREATE UNIQUE INDEX uniq_company_slug ON company (slug)');
        $this->addSql('ALTER TABLE company DROP email');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE company ADD email VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE company SET email = CONCAT(slug, \'@unknown.invalid\')');
        $this->addSql('ALTER TABLE company CHANGE email email VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX uniq_company_slug ON company');
        $this->addSql('CREATE UNIQUE INDEX uniq_company_email ON company (email)');
        $this->addSql('ALTER TABLE company DROP slug');
    }
}
