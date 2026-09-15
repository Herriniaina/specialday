<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260914081500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move the company/user relation: company_id now lives on user instead of user_id on company';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE company DROP FOREIGN KEY FK_4FBF094FA76ED395');
        $this->addSql('DROP INDEX IDX_4FBF094FA76ED395 ON company');
        $this->addSql('ALTER TABLE company DROP user_id');

        $this->addSql('ALTER TABLE user ADD company_id VARCHAR(36) DEFAULT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user
            ADD
              CONSTRAINT FK_8D93D649979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)
        SQL);
        $this->addSql('CREATE INDEX IDX_8D93D649979B1AD6 ON user (company_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649979B1AD6');
        $this->addSql('DROP INDEX IDX_8D93D649979B1AD6 ON user');
        $this->addSql('ALTER TABLE user DROP company_id');

        $this->addSql('ALTER TABLE company ADD user_id CHAR(36) DEFAULT NULL');
        $this->addSql('UPDATE company SET user_id = (SELECT id FROM user ORDER BY id LIMIT 1)');
        $this->addSql('ALTER TABLE company CHANGE user_id user_id CHAR(36) NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company
            ADD
              CONSTRAINT FK_4FBF094FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql('CREATE INDEX IDX_4FBF094FA76ED395 ON company (user_id)');
    }
}
