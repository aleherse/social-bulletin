<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Place\Command;

use SocialBulletin\Core\Application\Helper\JsonLinesReader;
use SocialBulletin\Core\Domain\Place\Boundary;
use SocialBulletin\Core\Domain\Place\InvalidPlaceFeed;
use SocialBulletin\Core\Domain\Place\Neighbourhood;
use SocialBulletin\Core\Domain\Place\PlaceFeature;
use SocialBulletin\Core\Domain\Place\PlaceRepository;

final readonly class ImportPlacesHandler
{
    public function __construct(
        private JsonLinesReader $reader,
        private PlaceRepository $places,
    ) {
    }

    /**
     * @throws InvalidPlaceFeed naming the first malformed line; nothing is replaced
     */
    public function __invoke(ImportPlacesCommand $command): void
    {
        $this->places->replaceAll($this->parse($this->reader->lines($command->source)));
    }

    /**
     * Lazy, so a national import never holds every place in memory at once.
     *
     * @param iterable<int, array<mixed>> $features
     *
     * @return \Generator<Boundary|Neighbourhood>
     */
    private function parse(iterable $features): \Generator
    {
        foreach ($features as $line => $feature) {
            yield PlaceFeature::parse($line, $feature);
        }
    }
}
