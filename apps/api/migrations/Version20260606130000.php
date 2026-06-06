<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260606130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create bulletin schema and users table for the walking skeleton.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS bulletin');
        $this->addSql(<<<'SQL'
CREATE TABLE bulletin.users (
    id UUID NOT NULL,
    email VARCHAR(320) NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id)
)
SQL);
        $this->addSql('CREATE UNIQUE INDEX users_email_unique ON bulletin.users (LOWER(email))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS bulletin.users');
        $this->addSql('DROP SCHEMA IF EXISTS bulletin');
    }
}
