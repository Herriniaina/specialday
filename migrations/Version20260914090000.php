<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260914090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop company.access_code/token and add user.is_active/email_verification_token';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_company_token ON company');
        $this->addSql('ALTER TABLE company DROP access_code, DROP token');

        $this->addSql('ALTER TABLE user ADD is_active TINYINT(1) DEFAULT 0 NOT NULL, ADD email_verification_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649C4995C67 ON user (email_verification_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8D93D649C4995C67 ON user');
        $this->addSql('ALTER TABLE user DROP is_active, DROP email_verification_token');

        $this->addSql('ALTER TABLE company ADD access_code VARCHAR(6) NOT NULL, ADD token VARCHAR(64) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_company_token ON company (token)');
    }
}
