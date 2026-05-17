<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Tests\Unit\Application\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SocialBulletin\Api\Application\ValueObject\EmailAddress;

final class EmailAddressTest extends TestCase
{
    public function test_valid_email_creates_value_object(): void
    {
        $email = new EmailAddress('user@example.com');

        self::assertSame('user@example.com', $email->value());
    }

    public function test_uppercase_email_is_normalised_to_lowercase(): void
    {
        $email = new EmailAddress('User@Example.COM');

        self::assertSame('user@example.com', $email->value());
    }

    public function test_mixed_case_local_part_is_normalised_to_lowercase(): void
    {
        $email = new EmailAddress('First.Last@Domain.org');

        self::assertSame('first.last@domain.org', $email->value());
    }

    #[DataProvider('invalidEmailProvider')]
    public function test_invalid_email_throws_invalid_argument_exception(string $invalid): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EmailAddress($invalid);
    }

    public static function invalidEmailProvider(): array
    {
        return [
            'empty string'       => [''],
            'missing at sign'    => ['userexample.com'],
            'missing local part' => ['@example.com'],
            'missing domain'     => ['user@'],
            'spaces in address'  => ['user @example.com'],
            'double at sign'     => ['user@@example.com'],
        ];
    }

    public function test_two_equal_emails_are_equal(): void
    {
        $a = new EmailAddress('user@example.com');
        $b = new EmailAddress('USER@EXAMPLE.COM');

        self::assertTrue($a->equals($b));
    }

    public function test_two_different_emails_are_not_equal(): void
    {
        $a = new EmailAddress('user@example.com');
        $b = new EmailAddress('other@example.com');

        self::assertFalse($a->equals($b));
    }
}
