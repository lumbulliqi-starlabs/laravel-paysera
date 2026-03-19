<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Starlabs\LaravelPaysera\Http\Controllers\PayseraController;

Route::name('paysera.')->group(function () {
    Route::post('paysera/callback', [PayseraController::class, 'callback'])
        ->name('callback');

    Route::post('paysera/initiate', [PayseraController::class, 'initiate'])
        ->name('initiate');

    Route::get('paysera/accept', [PayseraController::class, 'accept'])
        ->name('accept');

    Route::get('paysera/cancel', [PayseraController::class, 'cancel'])
        ->name('cancel');
});
