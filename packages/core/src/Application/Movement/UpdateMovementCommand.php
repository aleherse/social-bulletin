<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Movement;

use SocialBulletin\Core\Application\Helper\Command;
use Webmozart\Assert\Assert;

/**
 * Command: edit the fields of an existing `draft` movement.
 *
 * Fields the payload omitted are left uninitialised, so {@see Command::hasProperty()} tells them
 * from an explicit `null` — the movement already holds a value for each, and only an omission
 * should leave it standing.
 * Handled by {@see UpdateMovementHandler}.
 */
final readonly class UpdateMovementCommand extends Command
{
    // @phpstan-ignore property.uninitializedReadonly (unassigned when the payload omits it)
    public string $title;

    // @phpstan-ignore property.uninitializedReadonly (unassigned when the payload omits it)
    public string $description;

    // @phpstan-ignore property.uninitializedReadonly (unassigned when the payload omits it)
    public string $category;

    // @phpstan-ignore property.uninitializedReadonly (unassigned when the payload omits it)
    public string $area;

    // @phpstan-ignore property.uninitializedReadonly (unassigned when the payload omits it)
    public ?string $location;

    /**
     * Validates shape only, and assigns nothing for a key the payload never carried — an absent
     * field falls back to the movement's current value, which only the handler has loaded.
     *
     * @param array<string, mixed> $payload
     */
    private function __construct(
        public string $id,
        public string $authorId,
        array $payload,
    ) {
        if (\array_key_exists('title', $payload)) {
            Assert::nullOrString($payload['title'], 'title must be a string or null.');
            $this->title = $payload['title'] ?? '';
        }

        if (\array_key_exists('description', $payload)) {
            Assert::nullOrString($payload['description'], 'description must be a string or null.');
            $this->description = $payload['description'] ?? '';
        }

        if (\array_key_exists('category', $payload)) {
            Assert::nullOrString($payload['category'], 'category must be a string or null.');
            $this->category = $payload['category'] ?? '';
        }

        if (\array_key_exists('area', $payload)) {
            Assert::nullOrString($payload['area'], 'area must be a string or null.');
            $this->area = $payload['area'] ?? '';
        }

        if (\array_key_exists('location', $payload)) {
            Assert::nullOrString($payload['location'], 'location must be a string or null.');
            $this->location = $payload['location'];
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload, string $authorId, string $id): self
    {
        return new self($id, $authorId, $payload);
    }
}
