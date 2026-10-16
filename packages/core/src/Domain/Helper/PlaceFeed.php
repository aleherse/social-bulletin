<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Helper;

interface PlaceFeed
{
    /**
     * One decoded GeoJSON feature per line of `$source`, a path or a URL.
     *
     * @return iterable<int, array<mixed>> keyed by 1-based line number
     */
    public function features(string $source): iterable;
}
