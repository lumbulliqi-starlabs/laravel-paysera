<?php

declare(strict_types=1);

namespace Starlabs\LaravelPaysera\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Starlabs\LaravelPaysera\DTOs\PaymentResult;
use Starlabs\LaravelPaysera\Events\PaymentCompleted;
use Starlabs\LaravelPaysera\Exceptions\PayseraException;
use Starlabs\LaravelPaysera\Http\Controllers\PayseraController;
use Starlabs\LaravelPaysera\PayseraService;

class PayseraControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function callback_returns_ok_on_success(): void
    {
        Event::fake([PaymentCompleted::class]);

        $mockService = $this->createMock(PayseraService::class);
        $mockService->method('handleCallback')
            ->willReturn(PaymentResult::fromCallbackData([
                'status' => '1',
                'orderid' => 'ORDER-CB-1',
                'transactionid' => '123',
                'amount' => '5000',
                'currency' => 'EUR',
            ]));

        $controller = new PayseraController($mockService);
        $response = $controller->callback(Request::create('/paysera/callback', 'POST'));

        $this->assertEquals('OK', $response);
    }

    #[Test]
    public function callback_returns_error_on_exception(): void
    {
        $mockService = $this->createMock(PayseraService::class);
        $mockService->method('handleCallback')
            ->willThrowException(PayseraException::callbackValidationFailed('Invalid signature'));

        $controller = new PayseraController($mockService);
        $response = $controller->callback(Request::create('/paysera/callback', 'POST'));

        $this->assertEquals('ERROR', $response);
    }

    #[Test]
    public function accept_redirects_to_configured_url_in_ssr_mode(): void
    {
        config(['paysera.urls.accept_url' => '/checkout/success']);
        config(['paysera.api.enabled' => false]);

        $service = $this->app->make(PayseraService::class);
        $controller = new PayseraController($service);
        $response = $controller->accept(Request::create('/paysera/accept', 'GET'));

        $this->assertStringEndsWith('/checkout/success', $response->getTargetUrl());
        $this->assertEquals('accepted', session('paysera_status'));
    }

    #[Test]
    public function cancel_redirects_to_configured_url_in_ssr_mode(): void
    {
        config(['paysera.urls.cancel_url' => '/cart']);
        config(['paysera.api.enabled' => false]);

        $service = $this->app->make(PayseraService::class);
        $controller = new PayseraController($service);
        $response = $controller->cancel(Request::create('/paysera/cancel', 'GET'));

        $this->assertStringEndsWith('/cart', $response->getTargetUrl());
        $this->assertEquals('cancelled', session('paysera_status'));
    }

    #[Test]
    public function accept_returns_json_in_api_mode(): void
    {
        config(['paysera.urls.accept_url' => '/checkout/success']);
        config(['paysera.api.enabled' => true]);

        $service = $this->app->make(PayseraService::class);
        $controller = new PayseraController($service);
        $response = $controller->accept(Request::create('/paysera/accept', 'GET'));

        $this->assertEquals('/checkout/success', $response->getData(true)['redirect_url']);
        $this->assertEquals('accepted', $response->getData(true)['status']);
    }

    #[Test]
    public function cancel_returns_json_in_api_mode(): void
    {
        config(['paysera.urls.cancel_url' => '/cart']);
        config(['paysera.api.enabled' => true]);

        $service = $this->app->make(PayseraService::class);
        $controller = new PayseraController($service);
        $response = $controller->cancel(Request::create('/paysera/cancel', 'GET'));

        $this->assertEquals('/cart', $response->getData(true)['redirect_url']);
        $this->assertEquals('cancelled', $response->getData(true)['status']);
    }

    #[Test]
    public function initiate_returns_payment_url_and_transaction(): void
    {
        Event::fake();

        $mockService = $this->createMock(PayseraService::class);
        $mockService->method('initiate')
            ->willReturn([
                'payment_url' => 'https://bank.paysera.com/pay/?data=...',
                'transaction_id' => 5,
                'order_id' => 'ORDER-API-1',
            ]);

        $controller = new PayseraController($mockService);
        $request = Request::create('/api/paysera/initiate', 'POST', [
            'order_id' => 'ORDER-API-1',
            'amount' => '29.99',
            'description' => 'Test',
            'email' => 'test@example.com',
        ]);
        $response = $controller->initiate($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('paysera.com', $response->getData(true)['payment_url']);
        $this->assertEquals(5, $response->getData(true)['transaction_id']);
        $this->assertEquals('ORDER-API-1', $response->getData(true)['order_id']);
    }
}
