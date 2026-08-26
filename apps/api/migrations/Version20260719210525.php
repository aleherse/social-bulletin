<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ADR-0009: raw SQL over DBAL; all application objects live in the
 * `bulletin` schema.
 */
final class Version20260719210525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create bulletin.categories (seeded) and bulletin.movements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE bulletin.categories (
                id TEXT NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                PRIMARY KEY (id)
            )
            SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO bulletin.categories (id, sort_order) VALUES
                ('animal_rights', 10),
                ('anti-racism', 20),
                ('black_power', 30),
                ('cooperative', 40)
            SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE bulletin.movements (
                id UUID NOT NULL,
                author_id UUID NOT NULL,
                title VARCHAR(200) NOT NULL,
                description TEXT NOT NULL DEFAULT '',
                category TEXT NOT NULL,
                area TEXT NOT NULL,
                location TEXT,
                status TEXT NOT NULL DEFAULT 'draft',
                created_at TIMESTAMPTZ NOT NULL,
                updated_at TIMESTAMPTZ NOT NULL,
                PRIMARY KEY (id),
                CONSTRAINT movements_author_fk
                    FOREIGN KEY (author_id) REFERENCES bulletin.users (id),
                CONSTRAINT movements_category_fk
                    FOREIGN KEY (category) REFERENCES bulletin.categories (id),
                CONSTRAINT movements_area_check CHECK (area IN (
                    'international', 'national', 'state', 'province',
                    'region', 'municipality', 'neighborhood'
                )),
                CONSTRAINT movements_location_check
                    CHECK ((area = 'international') = (location IS NULL)),
                CONSTRAINT movements_status_check
                    CHECK (status IN ('draft', 'proposed', 'published'))
            )
            SQL);
        $this->addSql('CREATE INDEX movements_author_idx ON bulletin.movements (author_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE bulletin.movements');
        $this->addSql('DROP TABLE bulletin.categories');
    }
}
