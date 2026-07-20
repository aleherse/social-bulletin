<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Movement;

use SocialBulletin\Core\Helper\IdentityGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

final readonly class MovementService
{
    public function __construct(
        private MovementRepository $movements,
        private Categories $categories,
        private IdentityGenerator $identities,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws InvalidMovement when any field fails stage validation
     */
    public function create(
        string $authorId,
        string $title,
        string $description,
        string $category,
        string $area,
        ?string $location,
    ): Movement {
        $this->assertValidFields($title, $description, $category, $area, $location);

        $id = $this->identities->generate();
        Assert::uuid($id);

        $areaValue = Area::from($area);
        $movement = Movement::draft(
            $id,
            $authorId,
            $title,
            $description,
            $category,
            $areaValue,
            Area::International === $areaValue ? null : $location,
            new \DateTimeImmutable(),
        );
        $this->movements->save($movement);

        return $movement;
    }

    /**
     * @return list<Movement> newest first
     */
    public function byAuthor(string $authorId): array
    {
        Assert::uuid($authorId);

        return $this->movements->byAuthor($authorId);
    }

    /**
     * @throws MovementNotFound when unknown or owned by another user
     */
    public function authorMovement(string $id, string $authorId): Movement
    {
        $movement = $this->movements->byId($id);

        if (null === $movement || $movement->authorId !== $authorId) {
            throw new MovementNotFound($this->trans('movement.not_found'));
        }

        return $movement;
    }

    /**
     * @throws MovementNotFound when unknown or owned by another user
     * @throws MovementNotDraft when the movement already left `draft`
     * @throws InvalidMovement  when any field fails stage validation
     */
    public function update(
        string $id,
        string $authorId,
        string $title,
        string $description,
        string $category,
        string $area,
        ?string $location,
    ): Movement {
        $movement = $this->authorMovement($id, $authorId);

        if (MovementStatus::Draft !== $movement->status()) {
            throw new MovementNotDraft($this->trans('movement.not_draft'));
        }

        $this->assertValidFields($title, $description, $category, $area, $location);

        $areaValue = Area::from($area);
        $movement->edit(
            $title,
            $description,
            $category,
            $areaValue,
            Area::International === $areaValue ? null : $location,
            new \DateTimeImmutable(),
        );
        $this->movements->save($movement);

        return $movement;
    }

    /**
     * @throws MovementNotFound when unknown or owned by another user
     * @throws MovementNotDraft when the movement already left `draft`
     * @throws InvalidMovement  when the description is still empty
     */
    public function submit(string $id, string $authorId): Movement
    {
        $movement = $this->authorMovement($id, $authorId);

        if (MovementStatus::Draft !== $movement->status()) {
            throw new MovementNotDraft($this->trans('movement.not_draft'));
        }

        if ('' === trim($movement->description())) {
            throw new InvalidMovement([
                'description' => $this->trans('movement.description.required'),
            ], $this->trans('movement.invalid'));
        }

        $movement->submit(new \DateTimeImmutable());
        $this->movements->save($movement);

        return $movement;
    }

    /**
     * @throws InvalidMovement
     */
    private function assertValidFields(
        string $title,
        string $description,
        string $category,
        string $area,
        ?string $location,
    ): void {
        $errors = [];
        $title = trim($title);

        if ('' === $title) {
            $errors['title'] = $this->trans('movement.title.blank');
        } elseif (mb_strlen($title) > Movement::TITLE_MAX_LENGTH) {
            $errors['title'] = $this->trans('movement.title.too_long', [
                'limit' => Movement::TITLE_MAX_LENGTH,
            ]);
        }

        if (mb_strlen($description) > Movement::DESCRIPTION_MAX_LENGTH) {
            $errors['description'] = $this->trans('movement.description.too_long', [
                'limit' => Movement::DESCRIPTION_MAX_LENGTH,
            ]);
        }

        if ('' === $category) {
            $errors['category'] = $this->trans('movement.category.blank');
        } elseif (! $this->categories->exists($category)) {
            $errors['category'] = $this->trans('movement.category.unknown');
        }

        $areaValue = Area::tryFrom($area);

        if (null === $areaValue) {
            $errors['area'] = $this->trans('movement.area.invalid');
        } elseif (Area::International === $areaValue) {
            if (null !== $location && '' !== trim($location)) {
                $errors['location'] = $this->trans('movement.location.forbidden');
            }
        } elseif (null === $location || '' === trim($location)) {
            $errors['location'] = $this->trans('movement.location.blank');
        }

        if ([] !== $errors) {
            throw new InvalidMovement($errors, $this->trans('movement.invalid'));
        }
    }

    /**
     * @param array<string, int|string> $parameters
     */
    private function trans(string $key, array $parameters = []): string
    {
        return $this->translator->trans($key, $parameters, 'validators');
    }
}
