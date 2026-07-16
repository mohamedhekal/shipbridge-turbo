<?php

declare(strict_types=1);

namespace Hekal\ShipBridge\Turbo\Tests;

use Hekal\ShipBridge\ShipBridgeServiceProvider;
use Hekal\ShipBridge\Turbo\TurboServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ShipBridgeServiceProvider::class,
            TurboServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('shipbridge.default', 'turbo');
        $app['config']->set('shipbridge.drivers.turbo.base_url', 'https://backoffice.turbo-eg.com/external-api');
        $app['config']->set('shipbridge.drivers.turbo.authentication_key', 'test-auth-key');
        $app['config']->set('shipbridge.drivers.turbo.main_client_code', '55159');
        $app['config']->set('shipbridge.drivers.turbo.return_amount', 35);
    }
}
