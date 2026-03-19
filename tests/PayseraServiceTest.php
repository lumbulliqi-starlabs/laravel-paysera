<?php

declare(strict_types=1);

namespace Starlabs\LaravelPaysera\Tests;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Starlabs\LaravelPaysera\Enums\PaymentStatus;
use Starlabs\LaravelPaysera\Exceptions\PayseraException;
use Starlabs\LaravelPaysera\Models\PayseraTransaction;
use Starlabs\LaravelPaysera\PayseraService;

class PayseraServiceTest extends TestCase
{
    #[Test]
    public function it_throws_exception_when_project_id_missing(): void
    {
        config(['paysera.project_id' => null]);

        $this->expectException(PayseraException::class);
        $this->expectExceptionMessage("'project_id' is missing");

        $service = new PayseraService;
        $service->buildRequestData(['orderid' => 'TEST-1', 'amount' => 10.00]);
    }

    #[Test]
    public function it_throws_exception_when_password_missing(): void
    {
        config(['paysera.project_id' => 12345]);
        config(['paysera.sign_password' => null]);

        $this->expectException(PayseraException::class);
        $this->expectExceptionMessage("'sign_password' is missing");

        $service = new PayseraService;
        $service->buildRequestData(['orderid' => 'TEST-1', 'amount' => 10.00]);
    }

    #[Test]
    public function it_creates_transaction(): void
    {
        Event::fake();

        $service = new PayseraService;
        $transaction = $service->createTransaction(
            orderId: 'ORDER-TEST-1',
            amount: 29.99,
            description: 'Test order',
            email: 'test@example.com',
        );

        $this->assertInstanceOf(PayseraTransaction::class, $transaction);
        $this->assertEquals('ORDER-TEST-1', $transaction->order_id);
        $this->assertEquals(29.99, $transaction->amount);
        $this->assertEquals('EUR', $transaction->currency);
        $this->assertEquals('test@example.com', $transaction->payer_email);
        $this->assertEquals(PaymentStatus::REGISTERED, $transaction->status);
    }

    #[Test]
    public function it_fluent_setter_methods(): void
    {
        $service = (new PayseraService)
            ->setProjectId(99999)
            ->setPassword('my_password')
            ->setCurrency('USD')
            ->setTestMode(false);

        $this->assertInstanceOf(PayseraService::class, $service);
    }

    #[Test]
    public function it_returns_base_url(): void
    {
        $service = new PayseraService;
        $url = $service->getBaseUrl('ENG');

        $this->assertIsString($url);
        $this->assertStringContainsString('paysera', $url);
    }

    #[Test]
    public function payment_status_enum_has_correct_values(): void
    {
        $this->assertEquals(0, PaymentStatus::REGISTERED->value);
        $this->assertEquals(1, PaymentStatus::COMPLETED->value);
        $this->assertEquals(2, PaymentStatus::AUTHORIZED->value);
        $this->assertEquals(3, PaymentStatus::CANCELLED->value);
        $this->assertEquals(4, PaymentStatus::REFUSED->value);
    }

    #[Test]
    public function payment_status_enum_has_helper_methods(): void
    {
        $this->assertTrue(PaymentStatus::COMPLETED->isSuccessful());
        $this->assertTrue(PaymentStatus::REGISTERED->isPending());
        $this->assertTrue(PaymentStatus::AUTHORIZED->isPending());
        $this->assertFalse(PaymentStatus::CANCELLED->isSuccessful());
        $this->assertTrue(PaymentStatus::CANCELLED->isFailed());
        $this->assertTrue(PaymentStatus::REFUSED->isFailed());
    }
}
