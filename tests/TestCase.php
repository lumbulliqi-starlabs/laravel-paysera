<?php

namespace Starlabs\LaravelPaysera\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;
use Starlabs\LaravelPaysera\LaravelPayseraServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Starlabs\\LaravelPaysera\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelPayseraServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('paysera.project_id', 12345);
        config()->set('paysera.sign_password', 'secret_password');
        config()->set('paysera.currency', 'EUR');
        config()->set('paysera.test_mode', true);
        config()->set('paysera.urls.accept_url', 'https://example.com/accept');
        config()->set('paysera.urls.cancel_url', 'https://example.com/cancel');
        config()->set('paysera.urls.callback_url', 'https://example.com/callback');
        config()->set('paysera.routes.enabled', false);
        config()->set('paysera.api.enabled', false);

        foreach (File::allFiles(__DIR__.'/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
        }
    }
}
