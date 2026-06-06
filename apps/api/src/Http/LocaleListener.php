<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Http;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 16)]
final readonly class LocaleListener
{
    /** @param non-empty-list<string> $availableLocales */
    public function __construct(
        private array $availableLocales = ['en'],
        private string $fallbackLocale = 'en',
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $preferredLocale = $request->getPreferredLanguage($this->availableLocales);

        $request->setLocale($preferredLocale ?? $this->fallbackLocale);
    }
}
