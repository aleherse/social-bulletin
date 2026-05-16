<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Infrastructure\I18n;

use SocialBulletin\Api\Application\Port\TranslatorPort;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class SymfonyTranslatorAdapter implements TranslatorPort
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * @param array<string, string|int|float> $parameters
     */
    public function translate(string $id, array $parameters = [], string $domain = 'messages'): string
    {
        return $this->translator->trans($id, $parameters, $domain);
    }
}
