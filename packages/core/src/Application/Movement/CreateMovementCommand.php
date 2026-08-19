<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement;

use SocialBulletin\Core\Application\Helper\Command;
use Webmozart\Assert\Assert;

/**
 * Command: create a `draft` movement.
 *
 * Every field is initialised: a fresh movement has nothing to fall back to, so an omitted field
 * carries the empty value the domain then rejects like any other unusable one. `location` is the
 * exception only in that its empty value is `null` — absent and explicitly `null` both mean the
 * movement has no location.
 * Handled by {@see CreateMovementHandler}.
 */
final readonly class CreateMovementCommand extends Command
{
    public string $title;

    public string $description;

    public string $category;

    public string $area;

    public ?string $location;

    /**
     * Validates shape only; whether the fields make a valid draft is the domain's call.
     *
     * @param array<string, mixed> $payload
     */
    private function __construct(
        public string $authorId,
        array $payload,
    ) {
        $title = $payload['title'] ?? null;
        Assert::nullOrString($title, 'title must be a string or null.');
        $this->title = $title ?? '';

        $description = $payload['description'] ?? null;
        Assert::nullOrString($description, 'description must be a string or null.');
        $this->description = $description ?? '';

        $category = $payload['category'] ?? null;
        Assert::nullOrString($category, 'category must be a string or null.');
        $this->category = $category ?? '';

        $area = $payload['area'] ?? null;
        Assert::nullOrString($area, 'area must be a string or null.');
        $this->area = $area ?? '';

        $location = $payload['location'] ?? null;
        Assert::nullOrString($location, 'location must be a string or null.');
        $this->location = $location;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload, string $authorId): self
    {
        return new self($authorId, $payload);
    }
}
