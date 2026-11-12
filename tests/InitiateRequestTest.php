<?php

declare(strict_types=1);

namespace Starlabs\LaravelPaysera\Tests;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Starlabs\LaravelPaysera\Models\PayseraTransaction;
use Starlabs\LaravelPaysera\PayseraService;

class InitiateRequestTest extends TestCase
{
    /** @return array<string, string> the parameters inside the signed "data" blob */
    private function decode(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        parse_str((string) base64_decode(strtr($query['data'], '-_', '+/'), true), $params);

        return $params + ['sign' => $query['sign'] ?? null];
    }

    #[Test]
    public function initiate_builds_a_signed_url_with_the_required_urls(): void
    {
        Event::fake();

        $result = (new PayseraService)->initiate(orderId: 'ORDER-9', amount: 12.5, description: 'Team plan', email: 'buyer@example.com');

        $this->assertStringStartsWith('https://', $result['payment_url']);
        $params = $this->decode($result['payment_url']);
        $this->assertSame('ORDER-9', $params['orderid']);
        $this->assertSame('1250', $params['amount']);
        $this->assertSame('EUR', $params['currency']);
        $this->assertSame('https://example.com/accept', $params['accepturl']);
        $this->assertSame('https://example.com/cancel', $params['cancelurl']);
        $this->assertSame('https://example.com/callback', $params['callbackurl']);
        $this->assertSame('12345', $params['projectid']);
        $this->assertSame('1', $params['test']);
        $this->assertSame('Team plan', $params['paytext']);
        $this->assertSame('buyer@example.com', $params['p_email']);
        $this->assertNotEmpty($params['sign']);
        $this->assertSame($result['transaction_id'], PayseraTransaction::query()->sole()->id);
    }

    #[Test]
    public function initiate_prefers_urls_passed_by_the_caller(): void
    {
        Event::fake();

        $result = (new PayseraService)->initiate('ORDER-10', 5.0, extraData: [
            'accept_url' => 'https://shop.test/ok', 'cancel_url' => 'https://shop.test/no', 'callback_url' => 'https://shop.test/hook',
        ]);

        $params = $this->decode($result['payment_url']);
        $this->assertSame('https://shop.test/ok', $params['accepturl']);
        $this->assertSame('https://shop.test/no', $params['cancelurl']);
        $this->assertSame('https://shop.test/hook', $params['callbackurl']);
    }

    #[Test]
    public function build_request_data_keeps_the_urls_it_is_given(): void
    {
        $request = (new PayseraService)->buildRequestData(['orderid' => 'ORDER-11', 'amount' => 1.0, 'callbackurl' => 'https://shop.test/hook']);

        $this->assertArrayHasKey('data', $request);
        $this->assertArrayHasKey('sign', $request);
        parse_str((string) base64_decode(strtr($request['data'], '-_', '+/'), true), $params);
        $this->assertSame('https://shop.test/hook', $params['callbackurl']);
        $this->assertSame('https://example.com/accept', $params['accepturl']);
        $this->assertSame('100', $params['amount']);
    }
}
