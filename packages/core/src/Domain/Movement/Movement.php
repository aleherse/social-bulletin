<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Domain\Movement;

use Webmozart\Assert\Assert;

final class Movement
{
    public const TITLE_MAX_LENGTH = 200;
    public const DESCRIPTION_MAX_LENGTH = 20000;

    private \DateTimeImmutable $createdAt;

    private \DateTimeImmutable $updatedAt;

    private function __construct(
        public readonly string $id,
        public readonly string $authorId,
        private string $title,
        private string $description,
        private string $category,
        private Area $area,
        private ?string $location,
        private MovementStatus $status,
    ) {
    }

    /**
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
    ): self {
        Assert::uuid($id);
        Assert::uuid($authorId);
        $areaValue = self::assertValidFields($title, $description, $category, $area, $location);

        return new self(
            $id,
            $authorId,
            trim($title),
            $description,
            $category,
            $areaValue,
            Area::International === $areaValue ? null : trim((string) $location),
            MovementStatus::Draft,
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
        $movement = new self(
            $id,
            $authorId,
            $title,
            $description,
            $category,
            $area,
            $location,
            $status,
        );
        $movement->createdAt = $createdAt;
        $movement->updatedAt = $updatedAt;

        return $movement;
    }

    /**
     * @throws MovementNotDraft when the movement already left `draft`
     * @throws InvalidMovement  when any field fails stage validation
     */
    public function edit(
        string $title,
        string $description,
        string $category,
        string $area,
        ?string $location,
    ): void {
        $this->assertDraft();
        $areaValue = self::assertValidFields($title, $description, $category, $area, $location);

        $this->title = trim($title);
        $this->description = $description;
        $this->category = $category;
        $this->area = $areaValue;
        $this->location = Area::International === $areaValue ? null : trim((string) $location);
    }

    /**
     * FR-006: `draft` -> `proposed`, only with a non-empty description.
     *
     * @throws MovementNotDraft when the movement already left `draft`
     * @throws InvalidMovement  when the description is still empty
     */
    public function submit(): void
    {
        $this->assertDraft();

        if ('' === trim($this->description)) {
            throw new InvalidMovement([
                'description' => 'movement.description.required',
            ], 'movement.invalid');
        }

        $this->status = MovementStatus::Proposed;
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

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * FR-007: a movement only changes while it is a `draft`.
     *
     * @throws MovementNotDraft when the movement already left `draft`
     */
    private function assertDraft(): void
    {
        if (MovementStatus::Draft !== $this->status) {
            throw new MovementNotDraft('movement.not_draft');
        }
    }

    /**
     * @throws InvalidMovement when any field fails stage validation
     */
    private static function assertValidFields(
        string $title,
        string $description,
        string $category,
        string $area,
        ?string $location,
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
