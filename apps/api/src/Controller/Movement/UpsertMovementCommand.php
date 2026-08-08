<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use Webmozart\Assert\Assert;

final readonly class UpsertMovementCommand
{
    private function __construct(
        public ?string $title,
        public ?string $description,
        public ?string $category,
        public ?string $area,
        public ?string $location,
        public bool $locationProvided,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        $title = $payload['title'] ?? null;
        $description = $payload['description'] ?? null;
        $category = $payload['category'] ?? null;
        $area = $payload['area'] ?? null;
        $locationProvided = \array_key_exists('location', $payload);
        $location = $locationProvided ? $payload['location'] : null;

        Assert::nullOrString($title, 'title must be a string or null.');
        Assert::nullOrString($description, 'description must be a string or null.');
        Assert::nullOrString($category, 'category must be a string or null.');
        Assert::nullOrString($area, 'area must be a string or null.');
        Assert::nullOrString($location, 'location must be a string or null.');

        return new self($title, $description, $category, $area, $location, $locationProvided);
    }
}
