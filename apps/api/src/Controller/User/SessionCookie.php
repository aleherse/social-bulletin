<?php

declare(strict_types=1);

namespace App\Controller\User;

use Symfony\Component\HttpFoundation\Cookie;

final class SessionCookie
{
    public const NAME = 'token';

    private const LIFETIME = 3600;

    private function __construct()
    {
    }

    public static function create(string $jwt): Cookie
    {
        // ADR-0011: the JWT travels only in an httpOnly cookie.
        return Cookie::create(
            self::NAME,
            $jwt,
            time() + self::LIFETIME,
            '/',
            null,
            true,
            true,
            false,
            Cookie::SAMESITE_STRICT,
        );
    }
}
