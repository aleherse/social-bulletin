<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Movement;

use Webmozart\Assert\Assert;

final class Movement
{
    public const TITLE_MAX_LENGTH = 200;
    public const DESCRIPTION_MAX_LENGTH = 20000;

    private function __construct(
        public readonly string $id,
        public readonly string $authorId,
        private string $title,
        private string $description,
        private string $category,
        private Area $area,
        private ?string $location,
        private MovementStatus $status,
        public readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    public static function draft(
        string $id,
        string $authorId,
        string $title,
        string $description,
        string $category,
        Area $area,
        ?string $location,
        \DateTimeImmutable $now,
    ): self {
        Assert::uuid($id);
        Assert::uuid($authorId);
        self::assertValidFields($title, $description, $category, $area, $location);

        return new self(
            $id,
            $authorId,
            trim($title),
            $description,
            $category,
            $area,
            null === $location ? null : trim($location),
            MovementStatus::Draft,
            $now,
            $now,
        );
    }

    /**
     * Trusted hydration from persistence; skips draft-time validation.
     */
    public static function restore(
        string $id,
        string $authorId,
        string $title,
        string $description,
        string $category,
        Area $area,
        ?string $location,
        MovementStatus $status,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            $id,
            $authorId,
            $title,
            $description,
            $category,
            $area,
            $location,
            $status,
            $createdAt,
            $updatedAt,
        );
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function category(): string
    {
        return $this->category;
    }

    public function area(): Area
    {
        return $this->area;
    }

    public function location(): ?string
    {
        return $this->location;
    }

    public function status(): MovementStatus
    {
        return $this->status;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private static function assertValidFields(
        string $title,
        string $description,
        string $category,
        Area $area,
        ?string $location,
    ): void {
        Assert::stringNotEmpty(trim($title), 'A movement needs a title.');
        Assert::maxLength(trim($title), self::TITLE_MAX_LENGTH);
        Assert::maxLength($description, self::DESCRIPTION_MAX_LENGTH);
        Assert::stringNotEmpty($category, 'A movement needs a category.');

        if (Area::International === $area) {
            Assert::null($location, 'International movements carry no location.');

            return;
        }

        Assert::stringNotEmpty(
            trim((string) $location),
            'A location is required for the chosen area.',
        );
    }
}
