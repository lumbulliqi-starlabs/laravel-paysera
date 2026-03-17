<?php

namespace Starlabs\LaravelPaysera;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Starlabs\LaravelPaysera\Commands\LaravelPayseraCommand;

class LaravelPayseraServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-paysera')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_paysera_table')
            ->hasCommand(LaravelPayseraCommand::class);
    }
}
