<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260917073928 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create media_file table to store company-uploaded files (images, videos, audio).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE media_file (
              id VARCHAR(36) NOT NULL,
              company_id VARCHAR(36) NOT NULL,
              filename VARCHAR(255) NOT NULL,
              file_size BIGINT NOT NULL,
              file_type VARCHAR(100) NOT NULL,
              storage VARCHAR(20) NOT NULL,
              path VARCHAR(500) NOT NULL,
              created_by CHAR(36) NOT NULL,
              updated_by CHAR(36) DEFAULT NULL,
              deleted_by CHAR(36) DEFAULT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME DEFAULT NULL,
              deleted_at DATETIME DEFAULT NULL,
              INDEX IDX_4FD8E9C3979B1AD6 (company_id),
              INDEX IDX_4FD8E9C3DE12AB56 (created_by),
              INDEX IDX_4FD8E9C316FE72E1 (updated_by),
              INDEX IDX_4FD8E9C31F6FA0AF (deleted_by),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql('ALTER TABLE media_file ADD CONSTRAINT FK_4FD8E9C3979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE media_file ADD CONSTRAINT FK_4FD8E9C3DE12AB56 FOREIGN KEY (created_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE media_file ADD CONSTRAINT FK_4FD8E9C316FE72E1 FOREIGN KEY (updated_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE media_file ADD CONSTRAINT FK_4FD8E9C31F6FA0AF FOREIGN KEY (deleted_by) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE media_file DROP FOREIGN KEY FK_4FD8E9C3979B1AD6');
        $this->addSql('ALTER TABLE media_file DROP FOREIGN KEY FK_4FD8E9C3DE12AB56');
        $this->addSql('ALTER TABLE media_file DROP FOREIGN KEY FK_4FD8E9C316FE72E1');
        $this->addSql('ALTER TABLE media_file DROP FOREIGN KEY FK_4FD8E9C31F6FA0AF');
        $this->addSql('DROP TABLE media_file');
    }
}
