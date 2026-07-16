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
        $app['config']->set('shipbridge.drivers.turbo.base_url', 'https://turbo.test/v1');
        $app['config']->set('shipbridge.drivers.turbo.token', 'test-token');
    }
}
