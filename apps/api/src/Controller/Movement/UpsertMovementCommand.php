<?php

declare(strict_types=1);

namespace App\Controller\Movement;

use SocialBulletin\Core\Movement\DraftMovement;
use SocialBulletin\Core\Movement\EditMovement;
use SocialBulletin\Core\Movement\Movement;
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

    /**
     * Absent fields reach the domain empty, which rejects them with a field error.
     */
    public function toDraft(string $authorId): DraftMovement
    {
        return new DraftMovement(
            $authorId,
            $this->title ?? '',
            $this->description ?? '',
            $this->category ?? '',
            $this->area ?? '',
            $this->location,
        );
    }

    /**
     * PATCH semantics: absent fields keep their current value.
     */
    public function toEdit(Movement $current): EditMovement
    {
        return new EditMovement(
            $this->title ?? $current->title(),
            $this->description ?? $current->description(),
            $this->category ?? $current->category(),
            $this->area ?? $current->area()
                ->value,
            $this->locationProvided ? $this->location : $current->location(),
        );
    }
}
