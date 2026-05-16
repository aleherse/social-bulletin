<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Application\Port;

interface TranslatorPort
{
    /**
     * @param array<string, string|int|float> $parameters
     */
    public function translate(string $id, array $parameters = [], string $domain = 'messages'): string;
}
