<?php

declare(strict_types=1);

namespace Starlabs\LaravelPaysera\Tests;

use PHPUnit\Framework\Attributes\Test;
use Starlabs\LaravelPaysera\DTOs\PaymentResult;
use Starlabs\LaravelPaysera\Enums\PaymentStatus;

/** Statuses as documented at developers.paysera.com/guides/checkout-classic ("Processing callback"). */
class CallbackStatusTest extends TestCase
{
    #[Test]
    public function status_1_and_4_mean_the_payment_succeeded(): void
    {
        $this->assertSame(PaymentStatus::COMPLETED, PaymentStatus::fromCallbackStatus(1));
        $this->assertSame(PaymentStatus::EXECUTED_UNCONFIRMED, PaymentStatus::fromCallbackStatus(4));
        $this->assertTrue(PaymentStatus::fromCallbackStatus(1)->isSuccessful());
        $this->assertTrue(PaymentStatus::fromCallbackStatus(4)->isSuccessful());
    }

    #[Test]
    public function status_0_2_and_3_are_still_pending_not_cancelled(): void
    {
        foreach ([0, 2, 3] as $status) {
            $mapped = PaymentStatus::fromCallbackStatus($status);
            $this->assertTrue($mapped->isPending(), "status $status should be pending");
            $this->assertFalse($mapped->isFailed(), "status $status is not a failure");
            $this->assertFalse($mapped->isSuccessful(), "status $status is not a success");
        }

        $this->assertSame(PaymentStatus::PAYER_INFO, PaymentStatus::fromCallbackStatus(3));
    }

    #[Test]
    public function cancelled_and_refused_are_never_produced_by_a_callback(): void
    {
        foreach (range(0, 10) as $status) {
            $this->assertFalse(PaymentStatus::fromCallbackStatus($status)->isFailed(), "status $status");
        }

        $this->assertSame(100, PaymentStatus::CANCELLED->value);
        $this->assertSame(101, PaymentStatus::REFUSED->value);
    }

    #[Test]
    public function payment_result_exposes_the_paid_amount_after_conversion(): void
    {
        $result = PaymentResult::fromCallbackData([
            'orderid' => 'ORDER-1', 'status' => '1', 'amount' => '1000', 'currency' => 'EUR',
            'payamount' => '1070', 'paycurrency' => 'USD', 'requestid' => '555',
        ]);

        $this->assertTrue($result->isCompleted());
        $this->assertSame(10.0, $result->amount);
        $this->assertSame('EUR', $result->currency);
        $this->assertSame(10.7, $result->payAmount);
        $this->assertSame('USD', $result->payCurrency);
    }
}
