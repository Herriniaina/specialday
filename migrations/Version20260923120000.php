<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260923120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create company_prestation table (company + prestation category + price/quantity/unit)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE company_prestation (
              id CHAR(36) NOT NULL,
              company_id CHAR(36) NOT NULL,
              prestation_category_id CHAR(36) NOT NULL,
              price_amount INT NOT NULL,
              price_currency VARCHAR(10) NOT NULL,
              price_tax_included TINYINT(1) NOT NULL,
              quantity INT NOT NULL,
              unit VARCHAR(50) NOT NULL,
              created_at DATETIME NOT NULL,
              INDEX IDX_company_prestation_company (company_id),
              INDEX IDX_company_prestation_category (prestation_category_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company_prestation
            ADD
              CONSTRAINT FK_company_prestation_company FOREIGN KEY (company_id) REFERENCES company (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company_prestation
            ADD
              CONSTRAINT FK_company_prestation_category FOREIGN KEY (prestation_category_id) REFERENCES prestation_category (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE company_prestation DROP FOREIGN KEY FK_company_prestation_company');
        $this->addSql('ALTER TABLE company_prestation DROP FOREIGN KEY FK_company_prestation_category');
        $this->addSql('DROP TABLE company_prestation');
    }
}
