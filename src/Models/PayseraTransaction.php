<?php

declare(strict_types=1);

namespace Starlabs\LaravelPaysera\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Starlabs\LaravelPaysera\Enums\PaymentStatus;
use Starlabs\LaravelPaysera\Events\PaymentCancelled;
use Starlabs\LaravelPaysera\Events\PaymentCompleted;

/**
 * @property int $id
 * @property string $order_id
 * @property string|null $order_description
 * @property float $amount
 * @property string $currency
 * @property PaymentStatus $status
 * @property int|null $paysera_transaction_id
 * @property string|null $payer_email
 * @property string|null $payment_method
 * @property string|null $country
 * @property string|null $language
 * @property array|null $raw_response
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PayseraTransaction extends Model
{
    protected $fillable = [
        'order_id',
        'order_description',
        'amount',
        'currency',
        'status',
        'paysera_transaction_id',
        'payer_email',
        'payment_method',
        'country',
        'language',
        'raw_response',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'status' => PaymentStatus::class,
        'raw_response' => 'array',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => PaymentStatus::REGISTERED,
    ];

    public function scopeCompleted($query)
    {
        return $query->where('status', PaymentStatus::COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [PaymentStatus::REGISTERED, PaymentStatus::AUTHORIZED]);
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', [PaymentStatus::CANCELLED, PaymentStatus::REFUSED]);
    }

    public function markAsCompleted(): self
    {
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'completed_at' => now(),
        ]);

        PaymentCompleted::dispatch($this);

        return $this;
    }

    public function markAsCancelled(): self
    {
        $this->update(['status' => PaymentStatus::CANCELLED]);

        PaymentCancelled::dispatch($this);

        return $this;
    }

    public function markAsRefused(): self
    {
        $this->update(['status' => PaymentStatus::REFUSED]);

        return $this;
    }

    public function markAsAuthorized(): self
    {
        $this->update(['status' => PaymentStatus::AUTHORIZED]);

        return $this;
    }

    public static function findByOrderId(string $orderId): ?self
    {
        return static::where('order_id', $orderId)->first();
    }

    public static function findByPayseraTransactionId(int $transactionId): ?self
    {
        return static::where('paysera_transaction_id', $transactionId)->first();
    }
}
