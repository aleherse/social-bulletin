<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create bulletin schema and users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS bulletin');
        $this->addSql(<<<'SQL'
            CREATE TABLE bulletin.users (
                id UUID PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                created_at TIMESTAMPTZ NOT NULL
            )
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS bulletin.users');
        $this->addSql('DROP SCHEMA IF EXISTS bulletin');
    }
}
