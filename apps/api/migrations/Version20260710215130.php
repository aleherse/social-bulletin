<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ADR-0009: raw SQL over DBAL; all application objects live in the
 * `bulletin` schema.
 */
final class Version20260710215130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create schema bulletin and table bulletin.users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS bulletin');
        $this->addSql(<<<'SQL'
            CREATE TABLE bulletin.users (
                id UUID NOT NULL,
                email VARCHAR(320) NOT NULL,
                created_at TIMESTAMPTZ NOT NULL,
                PRIMARY KEY (id)
            )
            SQL);
        $this->addSql('CREATE UNIQUE INDEX users_email_unique ON bulletin.users (LOWER(email))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE bulletin.users');
    }
}
