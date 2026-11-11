<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Application\Helper;

use PhpSpec\ObjectBehavior;

final class JsonLinesReaderSpec extends ObjectBehavior
{
    private string $path = '';

    public function letGo(): void
    {
        if ('' !== $this->path && is_file($this->path)) {
            unlink($this->path);
        }
    }

    public function it_yields_each_object_keyed_by_its_line_number_skipping_blank_lines(): void
    {
        $this->write("{\"a\":1}\n\n   \n{\"b\":[2]}\n");

        $this->lines($this->path)->shouldYield([
            1 => [
                'a' => 1,
            ],
            4 => [
                'b' => [2],
            ],
        ]);
    }

    public function it_names_the_line_that_is_not_valid_json(): void
    {
        $this->write("{\"a\":1}\n{oops\n");

        $this->lines($this->path)->shouldFailWith('Invalid JSON on line 2 of the JSON lines source.');
    }

    public function it_names_the_line_that_is_not_an_object(): void
    {
        $this->write("{\"a\":1}\n42\n");

        $this->lines($this->path)->shouldFailWith('Line 2 of the JSON lines source is not a JSON object.');
    }

    public function it_fails_when_the_source_cannot_be_opened(): void
    {
        $this->lines('/nonexistent/places.geojsonl')->shouldFailWith('The JSON lines source could not be opened.');
    }

    public function getMatchers(): array
    {
        return [
            'failWith' => static function (iterable $subject, string $message): bool {
                try {
                    iterator_to_array($subject);
                } catch (\RuntimeException $exception) {
                    return $exception->getMessage() === $message;
                }

                return false;
            },
            'yield' => static fn (iterable $subject, array $expected): bool => iterator_to_array($subject) === $expected,
        ];
    }

    private function write(string $contents): void
    {
        $this->path = (string) tempnam(sys_get_temp_dir(), 'jsonl');
        file_put_contents($this->path, $contents);
    }
}
