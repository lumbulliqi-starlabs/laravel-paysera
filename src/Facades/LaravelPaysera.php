<?php

namespace Starlabs\LaravelPaysera\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Starlabs\LaravelPaysera\LaravelPaysera
 */
class LaravelPaysera extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Starlabs\LaravelPaysera\LaravelPaysera::class;
    }
}
