<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260910084753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create company table (id, name, email, created_at, access_code, token)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE company (id VARCHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, access_code VARCHAR(6) NOT NULL, token VARCHAR(64) NOT NULL, UNIQUE INDEX uniq_company_email (email), UNIQUE INDEX uniq_company_token (token), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE company');
    }
}
