<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260914100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create subscription table (plan/payment enums, one-to-one company relation, S3 storage quota)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE subscription (
              id CHAR(36) NOT NULL,
              company_id VARCHAR(36) NOT NULL,
              plan VARCHAR(20) NOT NULL DEFAULT 'free',
              storage_used_bytes BIGINT NOT NULL DEFAULT 0,
              storage_max_bytes BIGINT NOT NULL DEFAULT 5368709120,
              start_date DATETIME DEFAULT NULL,
              end_date DATETIME DEFAULT NULL,
              payment_method VARCHAR(20) NOT NULL,
              payment_status VARCHAR(20) NOT NULL DEFAULT 'incomplete',
              stripe_subscription_id VARCHAR(255) DEFAULT NULL,
              mobile_money_reference VARCHAR(255) DEFAULT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME NOT NULL,
              UNIQUE INDEX UNIQ_A3C664D3979B1AD6 (company_id),
              INDEX idx_subscription_expiration (end_date, payment_status),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              subscription
            ADD
              CONSTRAINT FK_A3C664D3979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3979B1AD6');
        $this->addSql('DROP TABLE subscription');
    }
}
