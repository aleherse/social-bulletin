<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Infrastructure\I18n;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LocaleSubscriber implements EventSubscriberInterface
{
    /** @param list<string> $supportedLocales */
    public function __construct(private readonly array $supportedLocales)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $preferred = $request->getPreferredLanguage($this->supportedLocales);
        $locale = $preferred ?: 'en';

        $request->setLocale($locale);
    }
}
