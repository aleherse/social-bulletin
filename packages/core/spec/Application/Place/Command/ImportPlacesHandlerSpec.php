<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Application\Place\Command;

use PhpSpec\Exception\Example\FailureException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use SocialBulletin\Core\Application\Place\Command\ImportPlacesCommand;
use SocialBulletin\Core\Domain\Helper\PlaceFeed;
use SocialBulletin\Core\Domain\Place\Boundary;
use SocialBulletin\Core\Domain\Place\InvalidPlaceFeed;
use SocialBulletin\Core\Domain\Place\Neighbourhood;
use SocialBulletin\Core\Domain\Place\PlaceRepository;

final class ImportPlacesHandlerSpec extends ObjectBehavior
{
    private const POLYGON = [
        'type' => 'Polygon',
        'coordinates' => [[[-1.97, 52.88], [-0.97, 52.88], [-0.97, 53.88], [-1.97, 53.88], [-1.97, 52.88]]],
    ];

    private const POINT = [
        'type' => 'Point',
        'coordinates' => [-1.4701, 53.3895],
    ];

    public function let(PlaceFeed $feed, PlaceRepository $places): void
    {
        $this->beConstructedWith($feed, $places);
    }

    public function it_replaces_the_places_with_every_parsed_feature_in_feed_order(
        PlaceFeed $feed,
        PlaceRepository $places,
    ): void {
        $feed->features('places.geojsonl')->willReturn([
            1 => self::boundary(),
            2 => self::neighbourhood(),
        ]);

        $expected = [
            new Boundary(13, 8, 'Sheffield', (string) json_encode(self::POLYGON)),
            new Neighbourhood(21, 'Kelham Island', (string) json_encode(self::POINT)),
        ];
        // A generator can be consumed once, and Prophecy may ask more than once.
        $consumed = null;
        $places->replaceAll(Argument::that(static function (iterable $fed) use (&$consumed, $expected): bool {
            $consumed ??= iterator_to_array($fed, false);

            return $consumed == $expected;
        }))->shouldBeCalled();

        $this->__invoke(ImportPlacesCommand::fromSource('places.geojsonl'));
    }

    public function it_fails_while_the_repository_consumes_a_malformed_line(
        PlaceFeed $feed,
        PlaceRepository $places,
    ): void {
        $feed->features('places.geojsonl')->willReturn([
            1 => self::boundary(),
            2 => self::neighbourhood(geometry: self::POLYGON),
        ]);
        $places->replaceAll(Argument::any())->will(static function (array $arguments): void {
            /** @var iterable<mixed> $fed */
            $fed = $arguments[0];
            try {
                iterator_to_array($fed, false);
            } catch (InvalidPlaceFeed $exception) {
                if (! str_contains($exception->getMessage(), 'line 2')) {
                    throw new FailureException(sprintf('Expected the message to name line 2, got "%s".', $exception->getMessage()));
                }

                throw $exception;
            }
        });

        $this->shouldThrow(InvalidPlaceFeed::class)
            ->during('__invoke', [ImportPlacesCommand::fromSource('places.geojsonl')]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function boundary(): array
    {
        return [
            'type' => 'Feature',
            'properties' => [
                'osm_id' => 13,
                'kind' => 'boundary',
                'admin_level' => 8,
                'name' => 'Sheffield',
            ],
            'geometry' => self::POLYGON,
        ];
    }

    /**
     * @param array<string, mixed> $geometry
     *
     * @return array<string, mixed>
     */
    private static function neighbourhood(array $geometry = self::POINT): array
    {
        return [
            'type' => 'Feature',
            'properties' => [
                'osm_id' => 21,
                'kind' => 'neighbourhood',
                'name' => 'Kelham Island',
            ],
            'geometry' => $geometry,
        ];
    }
}
