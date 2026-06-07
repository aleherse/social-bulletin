<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Step\Then;
use JmesPath\Env;
use Webmozart\Assert\Assert;

final class JmespathContext implements Context
{
    /** @var array<string, mixed> */
    private array $responseData = [];

    /** @param array<string, mixed> $responseData */
    public function setResponseData(array $responseData): void
    {
        $this->responseData = $responseData;
    }

    #[Then('the JSON response at :expression should equal :expected')]
    public function jsonResponseAtShouldEqual(string $expression, string $expected): void
    {
        try {
            $expectedValue = json_decode($expected, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $expectedValue = $expected;
        }

        Assert::eq(Env::search($expression, $this->responseData), $expectedValue);
    }
}
