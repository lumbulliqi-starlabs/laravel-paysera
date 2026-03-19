<?php

declare(strict_types=1);

namespace Starlabs\LaravelPaysera\DTOs;

use Starlabs\LaravelPaysera\Enums\PaymentStatus;

class PaymentResult
{
    public function __construct(
        public readonly PaymentStatus $status,
        public readonly ?int $transactionId,
        public readonly ?string $orderId,
        public readonly ?float $amount,
        public readonly ?string $currency,
        public readonly ?string $payerEmail,
        public readonly ?string $paymentMethod,
        public readonly array $rawData,
    ) {}

    public function isCompleted(): bool
    {
        return $this->status->isSuccessful();
    }

    public function isCancelled(): bool
    {
        return $this->status === PaymentStatus::CANCELLED;
    }

    public function isRefused(): bool
    {
        return $this->status === PaymentStatus::REFUSED;
    }

    public function isAuthorized(): bool
    {
        return $this->status === PaymentStatus::AUTHORIZED;
    }

    public function isRegistered(): bool
    {
        return $this->status === PaymentStatus::REGISTERED;
    }

    public static function fromCallbackData(array $data): self
    {
        return new self(
            status: PaymentStatus::fromCallbackStatus((int) ($data['status'] ?? 0)),
            transactionId: isset($data['transactionid']) ? (int) $data['transactionid'] : null,
            orderId: $data['orderid'] ?? null,
            amount: isset($data['amount']) ? (float) $data['amount'] / 100 : null,
            currency: $data['currency'] ?? null,
            payerEmail: $data['p_email'] ?? null,
            paymentMethod: $data['payment_method'] ?? null,
            rawData: $data,
        );
    }
}
