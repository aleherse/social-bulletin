<?php

declare(strict_types=1);

namespace SocialBulletin\Api\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final readonly class LocaleListener
{
    /**
     * @param list<string> $supportedLocales
     */
    public function __construct(
        private array $supportedLocales,
        private string $fallbackLocale,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $locale = $this->resolveLocale($request);
        $request->setLocale($locale);
    }

    private function resolveLocale(Request $request): string
    {
        $preferred = $request->getPreferredLanguage($this->supportedLocales);

        if (is_string($preferred) && in_array($preferred, $this->supportedLocales, true)) {
            return $preferred;
        }

        return $this->fallbackLocale;
    }
}
