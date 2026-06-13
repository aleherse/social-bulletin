<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create bulletin schema and users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE SCHEMA IF NOT EXISTS bulletin");
        $this->addSql("
            CREATE TABLE bulletin.users (
                id UUID NOT NULL DEFAULT gen_random_uuid(),
                email VARCHAR(255) NOT NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                CONSTRAINT users_pkey PRIMARY KEY (id),
                CONSTRAINT users_email_unique UNIQUE (email)
            )
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS bulletin.users");
        $this->addSql("DROP SCHEMA IF EXISTS bulletin");
    }
}
