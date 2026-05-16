<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Tests\Unit\Infrastructure\I18n;

use PHPUnit\Framework\TestCase;
use SocialBulletin\Api\Infrastructure\I18n\LocaleSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class LocaleSubscriberTest extends TestCase
{
    public function testSubscribesToKernelRequest(): void
    {
        $events = LocaleSubscriber::getSubscribedEvents();

        self::assertArrayHasKey(KernelEvents::REQUEST, $events);
    }

    public function testSetsLocaleToEnWhenAcceptLanguageIsUnsupported(): void
    {
        $subscriber = new LocaleSubscriber(['en']);

        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'fr');

        $event = $this->makeRequestEvent($request);
        $subscriber->onKernelRequest($event);

        self::assertSame('en', $request->getLocale());
    }

    public function testSetsLocaleFromAcceptLanguageWhenSupported(): void
    {
        $subscriber = new LocaleSubscriber(['en', 'fr']);

        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'fr');

        $event = $this->makeRequestEvent($request);
        $subscriber->onKernelRequest($event);

        self::assertSame('fr', $request->getLocale());
    }

    public function testDefaultsToEnWhenNoAcceptLanguageHeader(): void
    {
        $subscriber = new LocaleSubscriber(['en']);

        $request = Request::create('/');

        $event = $this->makeRequestEvent($request);
        $subscriber->onKernelRequest($event);

        self::assertSame('en', $request->getLocale());
    }

    private function makeRequestEvent(Request $request): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
