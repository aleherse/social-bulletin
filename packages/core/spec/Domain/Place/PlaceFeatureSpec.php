<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Domain\Place;

use PhpSpec\Exception\Example\FailureException;
use PhpSpec\ObjectBehavior;
use SocialBulletin\Core\Domain\Place\Boundary;
use SocialBulletin\Core\Domain\Place\InvalidPlaceFeed;
use SocialBulletin\Core\Domain\Place\Neighbourhood;
use SocialBulletin\Core\Domain\Place\PlaceFeature;

final class PlaceFeatureSpec extends ObjectBehavior
{
    private const POLYGON = [
        'type' => 'Polygon',
        'coordinates' => [[[-1.97, 52.88], [-0.97, 52.88], [-0.97, 53.88], [-1.97, 53.88], [-1.97, 52.88]]],
    ];

    private const MULTI_POLYGON = [
        'type' => 'MultiPolygon',
        'coordinates' => [[[[-1.97, 52.88], [-0.97, 52.88], [-0.97, 53.88], [-1.97, 53.88], [-1.97, 52.88]]]],
    ];

    private const POINT = [
        'type' => 'Point',
        'coordinates' => [-1.4701, 53.3895],
    ];

    public function it_parses_a_boundary_with_a_polygon(): void
    {
        $this->parse(1, self::boundary())->shouldBeLike(
            new Boundary(13, 8, 'Sheffield', (string) json_encode(self::POLYGON)),
        );
    }

    public function it_parses_a_boundary_with_a_multipolygon(): void
    {
        $this->parse(1, self::boundary(geometry: self::MULTI_POLYGON))->shouldBeLike(
            new Boundary(13, 8, 'Sheffield', (string) json_encode(self::MULTI_POLYGON)),
        );
    }

    public function it_parses_a_neighbourhood_with_a_point(): void
    {
        $this->parse(1, self::neighbourhood())->shouldBeLike(
            new Neighbourhood(21, 'Kelham Island', (string) json_encode(self::POINT)),
        );
    }

    public function it_rejects_an_unsupported_admin_level(): void
    {
        $this->shouldRejectLine3(self::boundary(properties: [
            'admin_level' => 9,
        ]));
    }

    public function it_rejects_a_feature_without_a_name(): void
    {
        $feature = self::boundary();
        unset($feature['properties']['name']);

        $this->shouldRejectLine3($feature);
    }

    public function it_rejects_a_feature_with_an_empty_name(): void
    {
        $this->shouldRejectLine3(self::neighbourhood(properties: [
            'name' => '',
        ]));
    }

    public function it_rejects_a_boundary_with_a_point(): void
    {
        $this->shouldRejectLine3(self::boundary(geometry: self::POINT));
    }

    public function it_rejects_a_neighbourhood_with_a_polygon(): void
    {
        $this->shouldRejectLine3(self::neighbourhood(geometry: self::POLYGON));
    }

    public function it_rejects_an_unknown_kind(): void
    {
        $this->shouldRejectLine3(self::boundary(properties: [
            'kind' => 'river',
        ]));
    }

    public function it_rejects_a_feature_without_an_osm_id(): void
    {
        $feature = self::boundary();
        unset($feature['properties']['osm_id']);

        $this->shouldRejectLine3($feature);
    }

    /**
     * @param array<string, mixed> $feature
     */
    private function shouldRejectLine3(array $feature): void
    {
        $this->shouldThrow(InvalidPlaceFeed::class)->during('parse', [3, $feature]);

        try {
            PlaceFeature::parse(3, $feature);
        } catch (InvalidPlaceFeed $exception) {
            if (! str_contains($exception->getMessage(), 'line 3')) {
                throw new FailureException(sprintf('Expected the message to name line 3, got "%s".', $exception->getMessage()));
            }
        }
    }

    /**
     * @param array<string, mixed> $properties
     * @param array<string, mixed> $geometry
     *
     * @return array<string, mixed>
     */
    private static function boundary(array $properties = [], array $geometry = self::POLYGON): array
    {
        return [
            'type' => 'Feature',
            'properties' => array_merge([
                'osm_id' => 13,
                'kind' => 'boundary',
                'admin_level' => 8,
                'name' => 'Sheffield',
            ], $properties),
            'geometry' => $geometry,
        ];
    }

    /**
     * @param array<string, mixed> $properties
     * @param array<string, mixed> $geometry
     *
     * @return array<string, mixed>
     */
    private static function neighbourhood(array $properties = [], array $geometry = self::POINT): array
    {
        return [
            'type' => 'Feature',
            'properties' => array_merge([
                'osm_id' => 21,
                'kind' => 'neighbourhood',
                'name' => 'Kelham Island',
            ], $properties),
            'geometry' => $geometry,
        ];
    }
}
