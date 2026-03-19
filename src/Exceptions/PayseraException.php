<?php

declare(strict_types=1);

namespace Starlabs\LaravelPaysera\Exceptions;

use Exception;

class PayseraException extends Exception
{
    public static function configMissing(string $key): self
    {
        return new self("Paysera configuration key '{$key}' is missing.");
    }

    public static function callbackValidationFailed(string $reason): self
    {
        return new self("Paysera callback validation failed: {$reason}");
    }

    public static function requestBuildFailed(string $reason): self
    {
        return new self("Paysera request build failed: {$reason}");
    }
}
