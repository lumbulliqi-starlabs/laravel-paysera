<?php

declare(strict_types=1);

namespace Starlabs\LaravelPaysera\Enums;

/**
 * Callback statuses as Paysera's classic Checkout documents them
 * (developers.paysera.com/guides/checkout-classic, "Processing callback"):
 *
 *   0  payment not executed
 *   1  payment successful
 *   2  order accepted, payment not executed yet
 *   3  additional information about the payer (not a result)
 *   4  payment executed, no confirmation of received funds will follow
 *
 * Paysera sends no callback for a cancelled or refused payment; the customer simply lands on
 * cancelurl. CANCELLED and REFUSED are AvePay-side states set by the application (for example
 * when a customer gives up), kept out of Paysera's 0..4 range so the two can never be confused.
 */
enum PaymentStatus: int
{
    case REGISTERED = 0;
    case COMPLETED = 1;
    case AUTHORIZED = 2;
    case PAYER_INFO = 3;
    case EXECUTED_UNCONFIRMED = 4;

    /** Set by the application, never by a Paysera callback. */
    case CANCELLED = 100;
    case REFUSED = 101;

    public function label(): string
    {
        return match ($this) {
            self::REGISTERED => 'Registered',
            self::COMPLETED => 'Completed',
            self::AUTHORIZED => 'Authorized',
            self::PAYER_INFO => 'Payer information',
            self::EXECUTED_UNCONFIRMED => 'Executed, unconfirmed funds',
            self::CANCELLED => 'Cancelled',
            self::REFUSED => 'Refused',
        };
    }

    /** The money is (or will be) there: statuses 1 and 4. */
    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED || $this === self::EXECUTED_UNCONFIRMED;
    }

    public function isPending(): bool
    {
        return $this === self::REGISTERED || $this === self::AUTHORIZED || $this === self::PAYER_INFO;
    }

    public function isFailed(): bool
    {
        return $this === self::CANCELLED || $this === self::REFUSED;
    }

    /** Only Paysera's documented values map; anything else is treated as "not executed". */
    public static function fromCallbackStatus(int $status): self
    {
        return match ($status) {
            1 => self::COMPLETED,
            2 => self::AUTHORIZED,
            3 => self::PAYER_INFO,
            4 => self::EXECUTED_UNCONFIRMED,
            default => self::REGISTERED,
        };
    }
}
