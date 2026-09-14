<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260914110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Extract subscription.plan/storage_max_bytes into a subscription_plan catalog table, joined via subscription_plan_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE subscription_plan (
              id CHAR(36) NOT NULL,
              plan VARCHAR(20) NOT NULL,
              storage BIGINT NOT NULL,
              UNIQUE INDEX UNIQ_EA664B63DD5A5B7D (plan),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);

        // Seed the plan catalog with the values previously hardcoded in the PlanEnum/default storage quota.
        // Adjust the per-plan storage quota afterwards from the "Plans d'abonnement" admin screen.
        $this->addSql(<<<'SQL'
            INSERT INTO subscription_plan (id, plan, storage) VALUES
              ('6e26bf2b-f5da-4fe2-bcb5-68c662713f3d', 'free', 5368709120),
              ('053c389a-b4e5-457e-9a30-1fc4ec0fc82a', 'standard', 5368709120),
              ('72a47199-98b3-4b9e-b08e-951fefc120a7', 'premium', 5368709120)
        SQL);

        $this->addSql('ALTER TABLE subscription ADD subscription_plan_id CHAR(36) DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE
              subscription s
              INNER JOIN subscription_plan sp ON sp.plan = s.plan
            SET
              s.subscription_plan_id = sp.id
        SQL);
        $this->addSql('ALTER TABLE subscription CHANGE subscription_plan_id subscription_plan_id CHAR(36) NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              subscription
            ADD
              CONSTRAINT FK_A3C664D39B8CE200 FOREIGN KEY (subscription_plan_id) REFERENCES subscription_plan (id)
        SQL);
        $this->addSql('CREATE INDEX IDX_A3C664D39B8CE200 ON subscription (subscription_plan_id)');
        $this->addSql('ALTER TABLE subscription DROP plan, DROP storage_max_bytes');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE
              subscription
            ADD
              plan VARCHAR(20) DEFAULT 'free' NOT NULL,
            ADD
              storage_max_bytes BIGINT DEFAULT 5368709120 NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE
              subscription s
              INNER JOIN subscription_plan sp ON sp.id = s.subscription_plan_id
            SET
              s.plan = sp.plan,
              s.storage_max_bytes = sp.storage
        SQL);
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D39B8CE200');
        $this->addSql('DROP INDEX IDX_A3C664D39B8CE200 ON subscription');
        $this->addSql('ALTER TABLE subscription DROP subscription_plan_id');
        $this->addSql('DROP TABLE subscription_plan');
    }
}
