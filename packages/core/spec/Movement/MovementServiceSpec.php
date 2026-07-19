<?php

declare(strict_types=1);

namespace spec\SocialBulletin\Core\Movement;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use SocialBulletin\Core\IdentityGenerator;
use SocialBulletin\Core\Movement\Area;
use SocialBulletin\Core\Movement\Categories;
use SocialBulletin\Core\Movement\InvalidMovement;
use SocialBulletin\Core\Movement\Movement;
use SocialBulletin\Core\Movement\MovementNotFound;
use SocialBulletin\Core\Movement\MovementRepository;
use SocialBulletin\Core\Movement\MovementStatus;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MovementServiceSpec extends ObjectBehavior
{
    private const ID = '0198f2f0-6d2c-7cf0-a2b8-222222222222';
    private const AUTHOR_ID = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

    public function let(
        MovementRepository $movements,
        Categories $categories,
        IdentityGenerator $identities,
        TranslatorInterface $translator,
    ): void {
        $this->beConstructedWith($movements, $categories, $identities, $translator);
        $translator->trans(Argument::cetera())->willReturn('translated');
    }

    public function it_creates_a_draft_movement(
        MovementRepository $movements,
        Categories $categories,
        IdentityGenerator $identities,
    ): void {
        $categories->exists('cooperative')->willReturn(true);
        $identities->generate()->willReturn(self::ID);
        $movements->save(Argument::that(
            static fn (Movement $movement): bool => self::ID === $movement->id
                && self::AUTHOR_ID === $movement->authorId
                && MovementStatus::Draft === $movement->status(),
        ))->shouldBeCalled();

        $movement = $this->create(
            self::AUTHOR_ID,
            'Community Gardens for Everyone',
            "## Why\nGardens for all.",
            'cooperative',
            'municipality',
            'Sheffield',
        );

        $movement->title()->shouldBe('Community Gardens for Everyone');
        $movement->status()->shouldBe(MovementStatus::Draft);
    }

    public function it_creates_a_draft_with_an_empty_description(
        MovementRepository $movements,
        Categories $categories,
        IdentityGenerator $identities,
    ): void {
        $categories->exists('cooperative')->willReturn(true);
        $identities->generate()->willReturn(self::ID);
        $movements->save(Argument::type(Movement::class))->shouldBeCalled();

        $movement = $this->create(
            self::AUTHOR_ID,
            'Community Gardens for Everyone',
            '',
            'cooperative',
            'municipality',
            'Sheffield',
        );

        $movement->description()->shouldBe('');
    }

    public function it_collects_a_translated_error_for_every_invalid_field(
        MovementRepository $movements,
        Categories $categories,
        TranslatorInterface $translator,
    ): void {
        $categories->exists('unknown')->willReturn(false);
        $movements->save(Argument::any())->shouldNotBeCalled();

        $translator->trans('movement.title.blank', [], 'validators')
            ->shouldBeCalled()->willReturn('A title is required.');
        $translator->trans('movement.category.unknown', [], 'validators')
            ->shouldBeCalled()->willReturn('Choose a category from the list.');
        $translator->trans('movement.area.invalid', [], 'validators')
            ->shouldBeCalled()->willReturn('Choose a valid area.');

        $this->shouldThrow(InvalidMovement::class)
            ->during('create', [self::AUTHOR_ID, '   ', '', 'unknown', 'galaxy', null]);
    }

    public function it_requires_a_location_for_local_areas(
        MovementRepository $movements,
        Categories $categories,
        TranslatorInterface $translator,
    ): void {
        $categories->exists('cooperative')->willReturn(true);
        $movements->save(Argument::any())->shouldNotBeCalled();
        $translator->trans('movement.location.blank', [], 'validators')
            ->shouldBeCalled()->willReturn('A location is required for the chosen area.');

        $this->shouldThrow(InvalidMovement::class)->during(
            'create',
            [self::AUTHOR_ID, 'Community Gardens for Everyone', '', 'cooperative', 'municipality', null],
        );
    }

    public function it_rejects_a_location_on_an_international_movement(
        MovementRepository $movements,
        Categories $categories,
        TranslatorInterface $translator,
    ): void {
        $categories->exists('cooperative')->willReturn(true);
        $movements->save(Argument::any())->shouldNotBeCalled();
        $translator->trans('movement.location.forbidden', [], 'validators')
            ->shouldBeCalled()->willReturn('International movements do not have a location.');

        $this->shouldThrow(InvalidMovement::class)->during(
            'create',
            [self::AUTHOR_ID, 'Global Climate Strike', '', 'cooperative', 'international', 'Sheffield'],
        );
    }

    public function it_lists_the_authors_movements(MovementRepository $movements): void
    {
        $movement = Movement::draft(
            self::ID,
            self::AUTHOR_ID,
            'Community Gardens for Everyone',
            '',
            'cooperative',
            Area::Municipality,
            'Sheffield',
            new \DateTimeImmutable(),
        );
        $movements->byAuthor(self::AUTHOR_ID)->willReturn([$movement]);

        $this->byAuthor(self::AUTHOR_ID)->shouldBe([$movement]);
    }

    public function it_returns_the_authors_movement(MovementRepository $movements): void
    {
        $movement = Movement::draft(
            self::ID,
            self::AUTHOR_ID,
            'Community Gardens for Everyone',
            '',
            'cooperative',
            Area::Municipality,
            'Sheffield',
            new \DateTimeImmutable(),
        );
        $movements->byId(self::ID)->willReturn($movement);

        $this->authorMovement(self::ID, self::AUTHOR_ID)->shouldBe($movement);
    }

    public function it_hides_movements_that_belong_to_another_user(
        MovementRepository $movements,
    ): void {
        $movement = Movement::draft(
            self::ID,
            self::AUTHOR_ID,
            'Community Gardens for Everyone',
            '',
            'cooperative',
            Area::Municipality,
            'Sheffield',
            new \DateTimeImmutable(),
        );
        $movements->byId(self::ID)->willReturn($movement);

        $this->shouldThrow(MovementNotFound::class)
            ->during('authorMovement', [self::ID, '0198f2f0-6d2c-7cf0-a2b8-333333333333']);
    }

    public function it_reports_an_unknown_movement_as_not_found(
        MovementRepository $movements,
    ): void {
        $movements->byId(self::ID)->willReturn(null);

        $this->shouldThrow(MovementNotFound::class)
            ->during('authorMovement', [self::ID, self::AUTHOR_ID]);
    }
}
