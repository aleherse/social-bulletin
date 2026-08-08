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

    /**
     * @param \Closure(): bool $categoryExists invoked only if the category isn't blank
     *
     * @throws InvalidMovement when any field fails stage validation
     */
    public static function draft(
        string $id,
        string $authorId,
        string $title,
        string $description,
        string $category,
        string $area,
        ?string $location,
        \Closure $categoryExists,
        \DateTimeImmutable $now,
    ): self {
        Assert::uuid($id);
        Assert::uuid($authorId);
        $areaValue = self::assertValidFields(
            $title,
            $description,
            $category,
            $area,
            $location,
            $categoryExists,
        );

        return new self(
            $id,
            $authorId,
            trim($title),
            $description,
            $category,
            $areaValue,
            Area::International === $areaValue ? null : trim((string) $location),
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

    /**
     * FR-007: fields can only change while the movement is a `draft`.
     *
     * @param \Closure(): bool $categoryExists invoked only if the category isn't blank
     *
     * @throws MovementNotDraft when the movement already left `draft`
     * @throws InvalidMovement  when any field fails stage validation
     */
    public function edit(
        string $title,
        string $description,
        string $category,
        string $area,
        ?string $location,
        \Closure $categoryExists,
        \DateTimeImmutable $now,
    ): void {
        if (MovementStatus::Draft !== $this->status) {
            throw new MovementNotDraft('movement.not_draft');
        }

        $areaValue = self::assertValidFields(
            $title,
            $description,
            $category,
            $area,
            $location,
            $categoryExists,
        );

        $this->title = trim($title);
        $this->description = $description;
        $this->category = $category;
        $this->area = $areaValue;
        $this->location = Area::International === $areaValue ? null : trim((string) $location);
        $this->updatedAt = $now;
    }

    /**
     * FR-006: `draft` -> `proposed`, only with a non-empty description.
     *
     * @throws MovementNotDraft when the movement already left `draft`
     * @throws InvalidMovement  when the description is still empty
     */
    public function submit(\DateTimeImmutable $now): void
    {
        if (MovementStatus::Draft !== $this->status) {
            throw new MovementNotDraft('movement.not_draft');
        }

        if ('' === trim($this->description)) {
            throw new InvalidMovement([
                'description' => 'movement.description.required',
            ], 'movement.invalid');
        }

        $this->status = MovementStatus::Proposed;
        $this->updatedAt = $now;
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

    /**
     * @param \Closure(): bool $categoryExists invoked only if the category isn't blank
     *
     * @throws InvalidMovement when any field fails stage validation
     */
    private static function assertValidFields(
        string $title,
        string $description,
        string $category,
        string $area,
        ?string $location,
        \Closure $categoryExists,
    ): Area {
        $errors = [];
        $trimmedTitle = trim($title);

        if ('' === $trimmedTitle) {
            $errors['title'] = 'movement.title.blank';
        } elseif (mb_strlen($trimmedTitle) > self::TITLE_MAX_LENGTH) {
            $errors['title'] = 'movement.title.too_long';
        }

        if (mb_strlen($description) > self::DESCRIPTION_MAX_LENGTH) {
            $errors['description'] = 'movement.description.too_long';
        }

        if ('' === $category) {
            $errors['category'] = 'movement.category.blank';
        } elseif (! $categoryExists()) {
            $errors['category'] = 'movement.category.unknown';
        }

        $areaValue = Area::tryFrom($area);

        if (null === $areaValue) {
            $errors['area'] = 'movement.area.invalid';
            $areaValue = Area::International;
        } elseif (Area::International === $areaValue) {
            if (null !== $location && '' !== trim($location)) {
                $errors['location'] = 'movement.location.forbidden';
            }
        } elseif (null === $location || '' === trim($location)) {
            $errors['location'] = 'movement.location.blank';
        }

        if ([] !== $errors) {
            throw new InvalidMovement($errors, 'movement.invalid');
        }

        return $areaValue;
    }
}
