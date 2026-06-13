<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
final class LocaleListener
{
    /** @var list<string> */
    private array $availableLocales = ['en'];

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $header = $request->headers->get('Accept-Language', 'en');
        $locale = strtolower(substr($header, 0, 2));

        if (!in_array($locale, $this->availableLocales, true)) {
            $locale = 'en';
        }

        $request->setLocale($locale);
    }
}
