<?php

declare(strict_types=1);

namespace SocialBulletin\Api;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/packages/*.yaml');
        $container->import('../config/services.yaml');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../src/Controller/', 'attribute');
        $routes->add('app.swagger_json', '/doc.json')
            ->controller('nelmio_api_doc.controller.swagger_json')
            ->methods(['GET']);
        $routes->add('app.swagger_yaml', '/doc.yaml')
            ->controller('nelmio_api_doc.controller.swagger_yaml')
            ->methods(['GET']);
    }
}
