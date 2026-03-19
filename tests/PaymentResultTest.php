<?php

declare(strict_types=1);

namespace Starlabs\LaravelPaysera\Tests;

use PHPUnit\Framework\Attributes\Test;
use Starlabs\LaravelPaysera\DTOs\PaymentResult;
use Starlabs\LaravelPaysera\Enums\PaymentStatus;

class PaymentResultTest extends TestCase
{
    #[Test]
    public function it_creates_from_callback_data(): void
    {
        $data = [
            'status' => '1',
            'transactionid' => '98765',
            'orderid' => 'ORDER-123',
            'amount' => '2999',
            'currency' => 'EUR',
            'p_email' => 'test@example.com',
            'payment_method' => 'visa',
        ];

        $result = PaymentResult::fromCallbackData($data);

        $this->assertEquals(PaymentStatus::COMPLETED, $result->status);
        $this->assertEquals(98765, $result->transactionId);
        $this->assertEquals('ORDER-123', $result->orderId);
        $this->assertEquals(29.99, $result->amount);
        $this->assertEquals('EUR', $result->currency);
        $this->assertEquals('test@example.com', $result->payerEmail);
        $this->assertEquals('visa', $result->paymentMethod);
    }

    #[Test]
    public function it_has_status_check_methods(): void
    {
        $completed = PaymentResult::fromCallbackData(['status' => '1']);
        $cancelled = PaymentResult::fromCallbackData(['status' => '3']);
        $refused = PaymentResult::fromCallbackData(['status' => '4']);
        $authorized = PaymentResult::fromCallbackData(['status' => '2']);
        $registered = PaymentResult::fromCallbackData(['status' => '0']);

        $this->assertTrue($completed->isCompleted());
        $this->assertFalse($completed->isCancelled());

        $this->assertTrue($cancelled->isCancelled());
        $this->assertTrue($refused->isRefused());
        $this->assertTrue($authorized->isAuthorized());
        $this->assertTrue($registered->isRegistered());
    }
}
