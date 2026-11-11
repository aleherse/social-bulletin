<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Helper;

/**
 * Reads newline-delimited JSON from a local path or, on the console Lambda,
 * a presigned HTTPS URL (ADR-0024).
 *
 * A plain class rather than a `final readonly` one, so that PHPSpec can double it
 * (`application-style-0001`).
 */
class JsonLinesReader
{
    /**
     * One decoded JSON object per non-blank line of `$source`.
     *
     * @return iterable<int, array<mixed>> keyed by 1-based line number
     */
    public function lines(string $source): iterable
    {
        $handle = @fopen($source, 'r');
        if (false === $handle) {
            throw new \RuntimeException('The JSON lines source could not be opened.');
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
            $decoded = json_decode($text, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException(sprintf('Invalid JSON on line %d of the JSON lines source.', $line), 0, $exception);
        }

        if (! \is_array($decoded)) {
            throw new \RuntimeException(sprintf('Line %d of the JSON lines source is not a JSON object.', $line));
        }

        return $decoded;
    }
}
