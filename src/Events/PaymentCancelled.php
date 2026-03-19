<?php

declare(strict_types=1);

namespace Starlabs\LaravelPaysera\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Starlabs\LaravelPaysera\Models\PayseraTransaction;

class PaymentCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly PayseraTransaction $transaction,
    ) {}
}
