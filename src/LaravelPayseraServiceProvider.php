<?php

declare(strict_types=1);

namespace Starlabs\LaravelPaysera;

use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Starlabs\LaravelPaysera\Http\Middleware\VerifyPayseraSignature;

class LaravelPayseraServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-paysera')
            ->hasConfigFile()
            ->hasMigration('create_paysera_table');
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(PayseraService::class, function () {
            return new PayseraService;
        });
    }

    public function bootingPackage(): void
    {
        $this->app['router']->aliasMiddleware('paysera', VerifyPayseraSignature::class);

        if ($this->app->runningInConsole()) {
            return;
        }

        if (! config('paysera.routes.enabled', true)) {
            return;
        }

        $prefix = config('paysera.routes.prefix', '');
        $middleware = config('paysera.routes.middleware', 'web');

        if (config('paysera.api.enabled')) {
            Route::middleware($middleware)
                ->prefix($prefix)
                ->group(__DIR__.'/Routes/paysera_api.php');
        } else {
            Route::middleware($middleware)
                ->prefix($prefix)
                ->group(__DIR__.'/Routes/paysera.php');
        }
    }
}
