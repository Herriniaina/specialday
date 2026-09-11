<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260911132205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nif, stat, rcs, phone, social profile fields and user_id FK to company table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company
            ADD
              nif VARCHAR(255) DEFAULT NULL,
            ADD
              stat VARCHAR(255) DEFAULT NULL,
            ADD
              rcs VARCHAR(255) DEFAULT NULL,
            ADD
              phone VARCHAR(15) DEFAULT NULL,
            ADD
              instagram_profile VARCHAR(255) DEFAULT NULL,
            ADD
              facebook_profile VARCHAR(255) DEFAULT NULL,
            ADD
              youtube_profile VARCHAR(255) DEFAULT NULL,
            ADD
              user_id CHAR(36) DEFAULT NULL
        SQL);
        // Backfill existing rows with an arbitrary user before enforcing NOT NULL (dev data only).
        $this->addSql('UPDATE company SET user_id = (SELECT id FROM user ORDER BY id LIMIT 1) WHERE user_id IS NULL');
        $this->addSql('ALTER TABLE company CHANGE user_id user_id CHAR(36) NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company
            ADD
              CONSTRAINT FK_4FBF094FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql('CREATE INDEX IDX_4FBF094FA76ED395 ON company (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE company DROP FOREIGN KEY FK_4FBF094FA76ED395');
        $this->addSql('DROP INDEX IDX_4FBF094FA76ED395 ON company');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company
            DROP
              nif,
            DROP
              stat,
            DROP
              rcs,
            DROP
              phone,
            DROP
              instagram_profile,
            DROP
              facebook_profile,
            DROP
              youtube_profile,
            DROP
              user_id
        SQL);
    }
}
