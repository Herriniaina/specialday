<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261007141446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop the user profile columns (email, name, phone): Keycloak is the only holder of the profile';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_user_email ON user');
        $this->addSql('ALTER TABLE user DROP email, DROP first_name, DROP last_name, DROP phone_number');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD email VARCHAR(255) NOT NULL, ADD first_name VARCHAR(255) DEFAULT NULL, ADD last_name VARCHAR(255) DEFAULT NULL, ADD phone_number VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_email ON user (email)');
    }
}
