<?php

declare(strict_types=1);

namespace Starlabs\LaravelPaysera\Facades;

use Illuminate\Support\Facades\Facade;
use Starlabs\LaravelPaysera\PayseraService;

/**
 * @method static void redirectToPayment(array $data)
 * @method static array buildRequestData(array $data)
 * @method static \Starlabs\LaravelPaysera\DTOs\PaymentResult handleCallback(\Illuminate\Http\Request $request)
 * @method static \Starlabs\LaravelPaysera\Models\PayseraTransaction createTransaction(string $orderId, float $amount, string $description = '', ?string $email = null, array $extraData = [])
 * @method static array initiate(string $orderId, float $amount, string $description = '', ?string $email = null, array $extraData = [])
 * @method static string getBaseUrl(string $lang = 'LIT')
 * @method static array getAvailablePaymentMethods(?float $amount = null, ?string $currency = null)
 * @method static self setProjectId(int $projectId)
 * @method static self setPassword(string $password)
 * @method static self setCurrency(string $currency)
 * @method static self setTestMode(bool $testMode)
 *
 * @see PayseraService
 */
class Paysera extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PayseraService::class;
    }
}
