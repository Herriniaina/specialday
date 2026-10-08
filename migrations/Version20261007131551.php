<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261007131551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move user credentials, roles and email verification to Keycloak: link each user to its Keycloak account';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8D93D649C4995C67 ON user');
        $this->addSql('ALTER TABLE user ADD identity_provider_id VARCHAR(36) DEFAULT NULL, DROP password, DROP roles, DROP is_active, DROP email_verification_token');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_identity_provider_id ON user (identity_provider_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_user_identity_provider_id ON user');
        $this->addSql('ALTER TABLE user ADD password VARCHAR(255) NOT NULL, ADD roles JSON NOT NULL, ADD is_active TINYINT DEFAULT 0 NOT NULL, ADD email_verification_token VARCHAR(64) DEFAULT NULL, DROP identity_provider_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649C4995C67 ON user (email_verification_token)');
    }
}
