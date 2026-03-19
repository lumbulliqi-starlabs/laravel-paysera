# Paysera payment integration for Laravel

[![Latest Version on Packagist](https://img.shields.io/pagist/v/starlabspro/laravel-paysera.svg?style=flat-square)](https://packagist.org/packages/starlabspro/laravel-paysera)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/starlabspro/laravel-paysera/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/starlabspro/laravel-paysera/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/starlabspro/laravel-paysera/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/starlabspro/laravel-paysera/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/starlabspro/laravel-paysera.svg?style=flat-square)](https://packagist.org/packages/starlabspro/laravel-paysera)

Laravel package for Paysera (WebToPay) payment gateway integration.

## Requirements

- PHP 8.2+
- Laravel 10.0+ / 11.0+ / 12.0+
- [Paysera account](https://www.paysera.com/) with a registered project

## Installation

```bash
composer require starlabspro/laravel-paysera
```

Publish the config and migration:

```bash
php artisan vendor:publish --tag="laravel-paysera-config"
php artisan vendor:publish --tag="laravel-paysera-migrations"
php artisan migrate
```

## Configuration

```env
PAYSERA_PROJECT_ID=12345
PAYSERA_SIGN_PASSWORD=your_sign_password
PAYSERA_CURRENCY=EUR
PAYSERA_TEST_MODE=true

PAYSERA_ACCEPT_URL=https://yoursite.com/payment/success
PAYSERA_CANCEL_URL=https://yoursite.com/payment/cancel
PAYSERA_CALLBACK_URL=https://yoursite.com/paysera/callback
```

## API Mode (Vue / SPA)

Set `PAYSERA_API_MODE=true` in `.env` to enable API mode. This returns JSON responses instead of server-side redirects.

```env
PAYSERA_API_MODE=true
```

## Usage

### Vue / SPA (API Mode)

```js
// Vue component
const response = await axios.post('/api/paysera/initiate', {
    order_id: order.id,
    amount: order.total,
    description: `Order #${order.id}`,
    email: user.email,
})

window.location.href = response.data.payment_url
```

Accept/cancel return JSON with a redirect URL for Vue:

```json
// GET /api/paysera/accept
{ "redirect_url": "https://yoursite.com/payment/success", "status": "accepted" }
```

### SSR / Blade (Default mode)

```php
use Starlabs\LaravelPaysera\Facades\Paysera;

$transaction = Paysera::createTransaction(
    orderId: $order->id,
    amount: $order->total,
    description: "Order #{$order->id}",
    email: $order->user_email,
);

Paysera::redirectToPayment([
    'orderid' => $transaction->order_id,
    'amount' => $transaction->amount,
    'description' => $transaction->order_description,
]);
```

## Callback / Webhook

```php
use Starlabs\LaravelPaysera\Facades\Paysera;
use Starlabs\LaravelPaysera\Models\PayseraTransaction;

Route::post('/paysera/callback', function (Request $request) {
    $result = Paysera::handleCallback($request);

    if ($result->isCompleted()) {
        $transaction = PayseraTransaction::findByOrderId($result->orderId);
        // process payment
    }

    return 'OK';
});
```

## Events

```php
// In EventServiceProvider
use Starlabs\LaravelPaysera\Events\PaymentCompleted;
use Starlabs\LaravelPaysera\Events\PaymentCancelled;
use Starlabs\LaravelPaysera\Events\PaymentReceived;

protected $listen = [
    PaymentCompleted::class => [SendConfirmationEmail::class],
    PaymentCancelled::class => [LogCancellation::class],
    PaymentReceived::class => [ProcessPaymentNotification::class],
];
```

## Payment Status Enum

```php
use Starlabs\LaravelPaysera\Enums\PaymentStatus;

$status->isSuccessful();  // COMPLETED
$status->isPending();     // REGISTERED or AUTHORIZED
$status->isFailed();       // CANCELLED or REFUSED
```

## Database

### Status codes

| Enum Case | Value | Meaning |
|---|---|---|
| `PaymentStatus::REGISTERED` | 0 | Payment registered |
| `PaymentStatus::COMPLETED` | 1 | Payment completed |
| `PaymentStatus::AUTHORIZED` | 2 | Payment authorized |
| `PaymentStatus::CANCELLED` | 3 | Payment cancelled |
| `PaymentStatus::REFUSED` | 4 | Payment refused |

### Scopes

```php
PayseraTransaction::completed()->get();
PayseraTransaction::pending()->get();
PayseraTransaction::failed()->get();
```

## Routes

### SSR Mode (default)

| Method | URI | Description |
|---|---|---|
| POST | `/paysera/callback` | Paysera server callback |
| GET | `/paysera/accept` | Redirect after payment |
| GET | `/paysera/cancel` | Redirect on cancel |

### API Mode (`PAYSERA_API_MODE=true`)

| Method | URI | Description |
|---|---|---|
| POST | `/api/paysera/initiate` | Create payment, returns URL |
| POST | `/api/paysera/callback` | Paysera server callback |
| GET | `/api/paysera/accept` | Returns JSON with redirect URL |
| GET | `/api/paysera/cancel` | Returns JSON with redirect URL |

Disable auto-routes:

```env
PAYSERA_ROUTES_ENABLED=false
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review our [security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Njazi Shehu](https://github.com/njazi-sl)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
