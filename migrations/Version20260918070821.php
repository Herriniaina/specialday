<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918070821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add media_type (image/video/audio) to media_file, backfilled from file_type.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE media_file ADD media_type VARCHAR(20) DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE media_file SET media_type = CASE
              WHEN file_type LIKE 'image/%' THEN 'image'
              WHEN file_type LIKE 'video/%' THEN 'video'
              WHEN file_type LIKE 'audio/%' THEN 'audio'
            END
        SQL);
        $this->addSql('ALTER TABLE media_file CHANGE media_type media_type VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE media_file DROP media_type');
    }
}
