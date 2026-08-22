<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement;

use SocialBulletin\Core\Application\Helper\Command;
use SocialBulletin\Core\Domain\Movement\MovementId;
use SocialBulletin\Core\Domain\User\UserId;
use Webmozart\Assert\Assert;

/**
 * Handled by {@see CreateMovementHandler}.
 */
final readonly class CreateMovementCommand extends Command
{
    public MovementId $id;

    public string $title;

    public string $description;

    public string $category;

    public string $area;

    public ?string $location;

    /**
     * @param array<string, mixed> $payload
     */
    private function __construct(
        public UserId $authorId,
        array $payload,
    ) {
        $this->id = MovementId::generate();

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
    public static function fromPayload(array $payload, UserId $authorId): self
    {
        return new self($authorId, $payload);
    }
}
