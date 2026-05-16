<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Tests\Unit\Application\Port;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SocialBulletin\Api\Application\Port\TranslatorPort;

final class TranslatorPortTest extends TestCase
{
    public function testInterfaceHasTranslateMethod(): void
    {
        $reflection = new \ReflectionClass(TranslatorPort::class);

        self::assertTrue($reflection->isInterface());
        self::assertTrue($reflection->hasMethod('translate'));
    }

    public function testTranslateMethodHasCorrectSignature(): void
    {
        $method = new ReflectionMethod(TranslatorPort::class, 'translate');
        $params = $method->getParameters();

        self::assertCount(3, $params);

        self::assertSame('id', $params[0]->getName());
        self::assertFalse($params[0]->isOptional());

        self::assertSame('parameters', $params[1]->getName());
        self::assertTrue($params[1]->isOptional());
        self::assertSame([], $params[1]->getDefaultValue());

        self::assertSame('domain', $params[2]->getName());
        self::assertTrue($params[2]->isOptional());
        self::assertSame('messages', $params[2]->getDefaultValue());
    }

    public function testTranslateMethodReturnsString(): void
    {
        $method = new ReflectionMethod(TranslatorPort::class, 'translate');

        self::assertNotNull($method->getReturnType());
        self::assertSame('string', (string) $method->getReturnType());
    }
}
