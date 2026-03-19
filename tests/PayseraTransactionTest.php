<?php

declare(strict_types=1);

namespace Starlabs\LaravelPaysera\Tests;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Starlabs\LaravelPaysera\Enums\PaymentStatus;
use Starlabs\LaravelPaysera\Events\PaymentCancelled;
use Starlabs\LaravelPaysera\Events\PaymentCompleted;
use Starlabs\LaravelPaysera\Events\PaymentReceived;
use Starlabs\LaravelPaysera\Models\PayseraTransaction;

class PayseraTransactionTest extends TestCase
{
    #[Test]
    public function it_creates_transaction_with_defaults(): void
    {
        $transaction = PayseraTransaction::create([
            'order_id' => 'ORDER-001',
            'amount' => 29.99,
            'currency' => 'EUR',
        ]);

        $this->assertDatabaseHas('paysera_transactions', [
            'order_id' => 'ORDER-001',
            'amount' => 29.99,
            'currency' => 'EUR',
            'status' => PaymentStatus::REGISTERED->value,
        ]);
    }

    #[Test]
    public function it_marks_transaction_as_completed(): void
    {
        $transaction = PayseraTransaction::create([
            'order_id' => 'ORDER-002',
            'amount' => 49.99,
            'currency' => 'EUR',
        ]);

        Event::fake([PaymentCompleted::class, PaymentReceived::class]);

        $transaction->markAsCompleted();

        $this->assertEquals(PaymentStatus::COMPLETED, $transaction->fresh()->status);
        $this->assertNotNull($transaction->fresh()->completed_at);

        Event::assertDispatched(PaymentCompleted::class);
    }

    #[Test]
    public function it_marks_transaction_as_cancelled(): void
    {
        $transaction = PayseraTransaction::create([
            'order_id' => 'ORDER-003',
            'amount' => 19.99,
            'currency' => 'EUR',
        ]);

        Event::fake([PaymentCancelled::class, PaymentReceived::class]);

        $transaction->markAsCancelled();

        $this->assertEquals(PaymentStatus::CANCELLED, $transaction->fresh()->status);

        Event::assertDispatched(PaymentCancelled::class);
    }

    #[Test]
    public function it_finds_by_order_id(): void
    {
        PayseraTransaction::create([
            'order_id' => 'ORDER-004',
            'amount' => 9.99,
            'currency' => 'EUR',
        ]);

        $found = PayseraTransaction::findByOrderId('ORDER-004');

        $this->assertNotNull($found);
        $this->assertEquals('ORDER-004', $found->order_id);
    }

    #[Test]
    public function it_finds_by_paysera_transaction_id(): void
    {
        $transaction = PayseraTransaction::create([
            'order_id' => 'ORDER-005',
            'amount' => 15.00,
            'currency' => 'EUR',
            'paysera_transaction_id' => 123456,
        ]);

        $found = PayseraTransaction::findByPayseraTransactionId(123456);

        $this->assertNotNull($found);
        $this->assertEquals($transaction->id, $found->id);
    }

    #[Test]
    public function scopes_filter_correctly(): void
    {
        PayseraTransaction::create(['order_id' => 'ORD-1', 'amount' => 10, 'currency' => 'EUR', 'status' => PaymentStatus::COMPLETED]);
        PayseraTransaction::create(['order_id' => 'ORD-2', 'amount' => 20, 'currency' => 'EUR', 'status' => PaymentStatus::REGISTERED]);
        PayseraTransaction::create(['order_id' => 'ORD-3', 'amount' => 30, 'currency' => 'EUR', 'status' => PaymentStatus::CANCELLED]);

        $this->assertEquals(1, PayseraTransaction::completed()->count());
        $this->assertEquals(1, PayseraTransaction::pending()->count());
        $this->assertEquals(1, PayseraTransaction::failed()->count());
    }
}
