<?php

declare(strict_types=1);

namespace App\Tests\Integration\Place;

use App\Messenger\QueryBus;
use App\Tests\Integration\IntegrationTestCase;
use SocialBulletin\Core\Application\Place\Model\Place;
use SocialBulletin\Core\Application\Place\Query\LookUpPlaceQuery;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

final class ImportPlacesConsoleCommandTest extends IntegrationTestCase
{
    public function testImportingReplacesTheLookupData(): void
    {
        $console = $this->import('places-reimport.geojsonl');

        self::assertSame(0, $console->getStatusCode(), $console->getDisplay());
        self::assertSame('Sheffield City', $this->nameAt(53.3811, -1.4701, 'municipality'));
        self::assertNull($this->nameAt(36.7033, -4.4366, 'national'));
    }

    public function testAMalformedImportChangesNothing(): void
    {
        $console = $this->import('places-malformed.geojsonl');

        self::assertNotSame(0, $console->getStatusCode());
        self::assertStringContainsString('line 2', $console->getDisplay());
        self::assertSame('Sheffield', $this->nameAt(53.3811, -1.4701, 'municipality'));
    }

    private function import(string $file): ApplicationTester
    {
        $kernel = self::$kernel;
        self::assertNotNull($kernel);

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $console = new ApplicationTester($application);
        $console->run([
            'command' => 'app:places:import',
            'source' => $kernel->getProjectDir().'/features/fixtures/'.$file,
        ]);

        return $console;
    }

    private function nameAt(float $lat, float $lon, string $area): ?string
    {
        $place = self::service(QueryBus::class)->dispatch(LookUpPlaceQuery::fromPayload([
            'lat' => $lat,
            'lon' => $lon,
            'area' => $area,
        ]));
        self::assertInstanceOf(Place::class, $place);

        return $place->name;
    }
}
