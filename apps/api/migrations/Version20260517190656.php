<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260517190656 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create api_users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE api_users (
                id UUID NOT NULL,
                email VARCHAR(254) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                roles JSONB NOT NULL DEFAULT '["ROLE_USER"]',
                terms_accepted_at TIMESTAMPTZ NOT NULL,
                registered_at TIMESTAMPTZ NOT NULL,
                CONSTRAINT api_users_pkey PRIMARY KEY (id),
                CONSTRAINT api_users_email_unique UNIQUE (email)
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE api_users');
    }
}
