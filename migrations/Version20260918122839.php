<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918122839 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add media_file.status/duration_seconds (async video transcoding) and encoded-minutes quota columns on subscription/subscription_plan.';
    }

    public function up(Schema $schema): void
    {
        // Every existing row was created synchronously under the old upload flow, so it is
        // already fully processed -> "ready" is the correct backfill value.
        $this->addSql("ALTER TABLE media_file ADD status VARCHAR(20) NOT NULL DEFAULT 'ready', ADD duration_seconds INT DEFAULT NULL");

        $this->addSql('ALTER TABLE subscription_plan ADD encoded_minutes_quota INT NOT NULL DEFAULT 0');
        $this->addSql(<<<'SQL'
            ALTER TABLE subscription
              ADD encoded_minutes_used INT NOT NULL DEFAULT 0,
              ADD encoded_minutes_period_started_at DATETIME DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subscription DROP encoded_minutes_used, DROP encoded_minutes_period_started_at');
        $this->addSql('ALTER TABLE subscription_plan DROP encoded_minutes_quota');
        $this->addSql('ALTER TABLE media_file DROP status, DROP duration_seconds');
    }
}
