<?php

declare(strict_types=1);

namespace App\Place;

use SocialBulletin\Core\Domain\Helper\PlaceFeed;

/**
 * Reads newline-delimited GeoJSON from a local path or, on the console Lambda,
 * a presigned HTTPS URL (ADR-0022).
 */
final readonly class GeoJsonSeqPlaceFeed implements PlaceFeed
{
    public function features(string $source): iterable
    {
        $handle = @fopen($source, 'r');
        if (false === $handle) {
            throw new \RuntimeException('The place feed could not be opened.');
        }

        try {
            $line = 0;
            while (false !== ($text = fgets($handle))) {
                ++$line;
                if ('' === trim($text)) {
                    continue;
                }

                yield $line => $this->decode($line, $text);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array<mixed>
     */
    private function decode(int $line, string $text): array
    {
        try {
            $feature = json_decode($text, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException(sprintf('Invalid JSON on line %d of the place feed.', $line), 0, $exception);
        }

        if (! \is_array($feature)) {
            throw new \RuntimeException(sprintf('Line %d of the place feed is not a JSON object.', $line));
        }

        return $feature;
    }
}
