<?php

declare(strict_types=1);

namespace Hekal\ShipBridge\Turbo;

use Hekal\ShipBridge\Facades\ShipBridge;
use Hekal\ShipBridge\Support\StatusNormalizer;
use Hekal\ShipBridge\Turbo\Support\PayloadFactory;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

final class TurboServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/turbo.php', 'shipbridge.drivers.turbo');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/turbo.php' => config_path('shipbridge-turbo.php'),
        ], 'shipbridge-turbo-config');

        ShipBridge::extend('turbo', function ($app, array $config): TurboDriver {
            /** @var array<string, string> $aliases */
            $aliases = config('shipbridge.status_aliases', []);
            /** @var array<string, string> $driverMap */
            $driverMap = $config['status_map'] ?? [];

            return new TurboDriver(
                client: new TurboClient($app->make(HttpFactory::class), $config),
                payloads: new PayloadFactory($config),
                normalizer: new StatusNormalizer(array_merge($aliases, $driverMap)),
                config: $config,
            );
        });
    }
}
