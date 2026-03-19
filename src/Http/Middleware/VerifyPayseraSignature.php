<?php

declare(strict_types=1);

namespace Starlabs\LaravelPaysera\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Starlabs\LaravelPaysera\Exceptions\PayseraException;
use Starlabs\LaravelPaysera\PayseraService;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VerifyPayseraSignature
{
    public function __construct(
        protected PayseraService $paysera,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $this->paysera->handleCallback($request);
        } catch (Throwable $e) {
            throw PayseraException::callbackValidationFailed($e->getMessage());
        }

        return $next($request);
    }
}
