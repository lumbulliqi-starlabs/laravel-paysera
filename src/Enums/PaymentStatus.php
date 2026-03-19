<?php

declare(strict_types=1);

namespace Starlabs\LaravelPaysera\Enums;

enum PaymentStatus: int
{
    case REGISTERED = 0;
    case COMPLETED = 1;
    case AUTHORIZED = 2;
    case CANCELLED = 3;
    case REFUSED = 4;

    public function label(): string
    {
        return match ($this) {
            self::REGISTERED => 'Registered',
            self::COMPLETED => 'Completed',
            self::AUTHORIZED => 'Authorized',
            self::CANCELLED => 'Cancelled',
            self::REFUSED => 'Refused',
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isPending(): bool
    {
        return $this === self::REGISTERED || $this === self::AUTHORIZED;
    }

    public function isFailed(): bool
    {
        return $this === self::CANCELLED || $this === self::REFUSED;
    }

    public static function fromCallbackStatus(int $status): self
    {
        return self::tryFrom($status) ?? self::REGISTERED;
    }
}
