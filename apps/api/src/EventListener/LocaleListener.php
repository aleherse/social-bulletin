<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Resolves the request locale from the Accept-Language header (ADR-0006).
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
final class LocaleListener
{
    private const FALLBACK_LOCALE = 'en';

    /**
     * @param list<string> $availableLocales
     */
    public function __construct(
        private readonly array $availableLocales = ['en'],
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $locale = $request->getPreferredLanguage($this->availableLocales) ?? self::FALLBACK_LOCALE;

        $request->setLocale($locale);
    }
}
