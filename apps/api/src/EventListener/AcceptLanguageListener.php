<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

final class AcceptLanguageListener
{
    private const DEFAULT_LOCALE = 'en';

    /** @param list<string> $availableLocales */
    public function __construct(
        private readonly array $availableLocales = ['en'],
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $preferredLanguage = $request->getPreferredLanguage($this->availableLocales);
        $locale = $preferredLanguage ?? self::DEFAULT_LOCALE;

        $request->setLocale($locale);
    }
}
