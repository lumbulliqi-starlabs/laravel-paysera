<?php

declare(strict_types=1);

namespace Starlabs\LaravelPaysera;

use Illuminate\Http\Request;
use Starlabs\LaravelPaysera\DTOs\PaymentResult;
use Starlabs\LaravelPaysera\Enums\PaymentStatus;
use Starlabs\LaravelPaysera\Events\PaymentReceived;
use Starlabs\LaravelPaysera\Exceptions\PayseraException;
use Starlabs\LaravelPaysera\Models\PayseraTransaction;
use WebToPay;
use WebToPayException;

class PayseraService
{
    protected ?int $projectId = null;

    protected ?string $password = null;

    protected string $currency = 'EUR';

    protected bool $testMode = true;

    protected array $defaultData = [];

    public function __construct()
    {
        $this->projectId = config('paysera.project_id');
        $this->password = config('paysera.sign_password');
        $this->currency = config('paysera.currency', 'EUR');
        $this->testMode = config('paysera.test_mode', true);
        $this->defaultData = [
            'currency' => $this->currency,
            'test' => (int) $this->testMode,
            'accepturl' => config('paysera.urls.accept_url'),
            'cancelurl' => config('paysera.urls.cancel_url'),
            'callbackurl' => config('paysera.urls.callback_url'),
            'country' => config('paysera.defaults.country'),
            'lang' => config('paysera.defaults.language'),
            'payment' => config('paysera.defaults.payment_method'),
        ];
    }

    public function setProjectId(int $projectId): self
    {
        $this->projectId = $projectId;

        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function setTestMode(bool $testMode): self
    {
        $this->testMode = $testMode;

        return $this;
    }

    public function getBaseUrl(string $lang = 'LIT'): string
    {
        return WebToPay::getPaymentUrl($lang);
    }

    public function initiate(
        string $orderId,
        float $amount,
        string $description = '',
        ?string $email = null,
        array $extraData = [],
    ): array {
        $this->ensureCredentials();

        $transaction = $this->createTransaction($orderId, $amount, $description, $email, $extraData);

        $requestData = $this->buildRequestData([
            'orderid' => $orderId,
            'amount' => $amount,
            'description' => $description,
        ]);

        $requestData['accepturl'] = $extraData['accept_url'] ?? config('paysera.urls.accept_url');
        $requestData['cancelurl'] = $extraData['cancel_url'] ?? config('paysera.urls.cancel_url');
        $requestData['callbackurl'] = $extraData['callback_url'] ?? config('paysera.urls.callback_url');

        $factory = new \WebToPay_Factory([
            \WebToPay_Config::PARAM_PROJECT_ID => $this->projectId,
            \WebToPay_Config::PARAM_PASSWORD => $this->password,
        ]);

        $url = $factory->getRequestBuilder()->buildRequestUrlFromData($requestData);

        return [
            'payment_url' => $url,
            'transaction_id' => $transaction->id,
            'order_id' => $orderId,
        ];
    }

    public function buildRequestData(array $data): array
    {
        $this->ensureCredentials();

        $requestData = array_merge($this->defaultData, $data);
        $requestData['projectid'] = $this->projectId;
        $requestData['sign_password'] = $this->password;
        $requestData['currency'] = $requestData['currency'] ?? $this->currency;

        if (isset($requestData['amount'])) {
            $requestData['amount'] = (int) round($requestData['amount'] * 100);
        }

        unset($requestData['accepturl'], $requestData['cancelurl'], $requestData['callbackurl']);

        try {
            return WebToPay::buildRequest($requestData);
        } catch (WebToPayException $e) {
            throw PayseraException::requestBuildFailed($e->getMessage());
        }
    }

    public function redirectToPayment(array $data): void
    {
        $requestData = $this->buildRequestData($data);
        $requestData['accepturl'] = $data['accepturl'] ?? config('paysera.urls.accept_url');
        $requestData['cancelurl'] = $data['cancelurl'] ?? config('paysera.urls.cancel_url');
        $requestData['callbackurl'] = $data['callbackurl'] ?? config('paysera.urls.callback_url');

        WebToPay::redirectToPayment($requestData);
    }

    public function createTransaction(
        string $orderId,
        float $amount,
        string $description = '',
        ?string $email = null,
        array $extraData = [],
    ): PayseraTransaction {
        return PayseraTransaction::create([
            'order_id' => $orderId,
            'order_description' => $description,
            'amount' => $amount,
            'currency' => $this->currency,
            'status' => PaymentStatus::REGISTERED,
            'payer_email' => $email,
            'country' => $extraData['country'] ?? null,
            'language' => $extraData['language'] ?? null,
        ]);
    }

    public function handleCallback(Request $request): PaymentResult
    {
        $this->ensureCredentials();

        try {
            $data = WebToPay::validateAndParseData($request->all(), $this->projectId, $this->password);
        } catch (WebToPayException $e) {
            throw PayseraException::callbackValidationFailed($e->getMessage());
        }

        $result = PaymentResult::fromCallbackData($data);

        $transaction = $this->resolveTransaction($data);

        if ($transaction) {
            $transaction->update([
                'paysera_transaction_id' => $data['transactionid'] ?? null,
                'payer_email' => $data['p_email'] ?? $transaction->payer_email,
                'payment_method' => $data['payment_method'] ?? $transaction->payment_method,
                'raw_response' => $data,
            ]);

            $this->updateTransactionStatus($transaction, $result->status);
        }

        PaymentReceived::dispatch($transaction, $result->status);

        return $result;
    }

    public function getAvailablePaymentMethods(?float $amount = null, ?string $currency = null): array
    {
        $this->ensureCredentials();

        $amountInCents = $amount !== null ? (int) round($amount * 100) : null;

        $paymentList = WebToPay::getPaymentMethodList(
            $this->projectId,
            $amountInCents,
            $currency ?? $this->currency,
        );

        return $this->formatPaymentMethods($paymentList);
    }

    protected function resolveTransaction(array $data): ?PayseraTransaction
    {
        $orderId = $data['orderid'] ?? null;

        if (! $orderId) {
            return null;
        }

        return PayseraTransaction::findByOrderId($orderId);
    }

    protected function updateTransactionStatus(PayseraTransaction $transaction, PaymentStatus $status): void
    {
        match ($status) {
            PaymentStatus::COMPLETED => $transaction->markAsCompleted(),
            PaymentStatus::CANCELLED => $transaction->markAsCancelled(),
            PaymentStatus::REFUSED => $transaction->markAsRefused(),
            PaymentStatus::AUTHORIZED => $transaction->markAsAuthorized(),
            default => null,
        };
    }

    protected function formatPaymentMethods($paymentList): array
    {
        $methods = [];

        foreach ($paymentList as $group) {
            foreach ($group as $item) {
                $methods[] = [
                    'key' => $item->getKey(),
                    'name' => $item->getName(),
                    'logo_url' => $item->getLogoUrl(),
                    'currency' => $item->getCurrency(),
                    'min_amount' => $item->getMinAmount(),
                    'max_amount' => $item->getMaxAmount(),
                ];
            }
        }

        return $methods;
    }

    protected function ensureCredentials(): void
    {
        if ($this->projectId === null) {
            throw PayseraException::configMissing('project_id');
        }

        if ($this->password === null) {
            throw PayseraException::configMissing('sign_password');
        }
    }
}
