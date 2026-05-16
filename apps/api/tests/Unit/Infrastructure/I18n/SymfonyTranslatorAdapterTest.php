<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Tests\Unit\Infrastructure\I18n;

use PHPUnit\Framework\TestCase;
use SocialBulletin\Api\Application\Port\TranslatorPort;
use SocialBulletin\Api\Infrastructure\I18n\SymfonyTranslatorAdapter;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

final class SymfonyTranslatorAdapterTest extends TestCase
{
    private SymfonyTranslatorAdapter $adapter;

    protected function setUp(): void
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            'error.service_unavailable' => 'Service temporarily unavailable.',
            'greeting' => 'Hello, {name}!',
        ], 'en', 'messages');

        $this->adapter = new SymfonyTranslatorAdapter($translator);
    }

    public function testImplementsTranslatorPort(): void
    {
        self::assertInstanceOf(TranslatorPort::class, $this->adapter);
    }

    public function testTranslatesAKnownKey(): void
    {
        $result = $this->adapter->translate('error.service_unavailable');

        self::assertSame('Service temporarily unavailable.', $result);
    }

    public function testTranslatesWithParameters(): void
    {
        $result = $this->adapter->translate('greeting', ['{name}' => 'Alice']);

        self::assertSame('Hello, Alice!', $result);
    }

    public function testFallsBackToKeyWhenTranslationMissing(): void
    {
        $result = $this->adapter->translate('non.existent.key');

        self::assertSame('non.existent.key', $result);
    }

    public function testRespectsCustomDomain(): void
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', ['field.required' => 'This field is required.'], 'en', 'validators');

        $adapter = new SymfonyTranslatorAdapter($translator);

        self::assertSame('This field is required.', $adapter->translate('field.required', [], 'validators'));
    }
}
