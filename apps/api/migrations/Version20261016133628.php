<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ADR-0024: OpenStreetMap places for the location lookup, replaced wholesale by
 * each `app:places:import`. PostGIS goes into `public`, outside the application
 * schema, so its types and functions resolve through the default search path.
 */
final class Version20261016133628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enable PostGIS and create bulletin.place_boundaries and bulletin.place_neighbourhoods';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS postgis WITH SCHEMA public');
        $this->addSql(<<<'SQL'
            CREATE TABLE bulletin.place_boundaries (
                osm_id BIGINT NOT NULL,
                admin_level SMALLINT NOT NULL,
                name TEXT NOT NULL,
                geom public.geometry(MultiPolygon, 4326) NOT NULL,
                PRIMARY KEY (osm_id),
                CONSTRAINT place_boundaries_admin_level_check CHECK (admin_level IN (2, 4, 6, 7, 8, 10)),
                CONSTRAINT place_boundaries_name_check CHECK (name <> '')
            )
            SQL);
        $this->addSql('CREATE INDEX place_boundaries_geom_idx ON bulletin.place_boundaries USING GIST (geom)');
        $this->addSql('CREATE INDEX place_boundaries_admin_level_idx ON bulletin.place_boundaries (admin_level)');
        $this->addSql(<<<'SQL'
            CREATE TABLE bulletin.place_neighbourhoods (
                osm_id BIGINT NOT NULL,
                name TEXT NOT NULL,
                geom public.geometry(Point, 4326) NOT NULL,
                PRIMARY KEY (osm_id),
                CONSTRAINT place_neighbourhoods_name_check CHECK (name <> '')
            )
            SQL);
        $this->addSql('CREATE INDEX place_neighbourhoods_geom_idx ON bulletin.place_neighbourhoods USING GIST (geom)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE bulletin.place_neighbourhoods');
        $this->addSql('DROP TABLE bulletin.place_boundaries');
        $this->addSql('DROP EXTENSION postgis');
    }
}
