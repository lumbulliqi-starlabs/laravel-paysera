<?php

declare(strict_types=1);

namespace Starlabs\LaravelPaysera\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Starlabs\LaravelPaysera\PayseraService;
use Throwable;

class PayseraController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct(
        protected PayseraService $paysera,
    ) {}

    public function callback(Request $request): string
    {
        try {
            $this->paysera->handleCallback($request);
        } catch (Throwable $e) {
            report($e);

            return 'ERROR';
        }

        return 'OK';
    }

    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:1000'],
            'email' => ['nullable', 'email'],
            'country' => ['nullable', 'string', 'size:2'],
            'language' => ['nullable', 'string', 'max:3'],
        ]);

        $data = $this->paysera->initiate(
            orderId: $validated['order_id'],
            amount: (float) $validated['amount'],
            description: $validated['description'] ?? '',
            email: $validated['email'] ?? null,
            extraData: [
                'country' => $validated['country'] ?? null,
                'language' => $validated['language'] ?? null,
            ],
        );

        return response()->json($data);
    }

    public function accept(Request $request): RedirectResponse|JsonResponse
    {
        if (config('paysera.api.enabled')) {
            return response()->json([
                'redirect_url' => config('paysera.urls.accept_url'),
                'status' => 'accepted',
            ]);
        }

        $redirectUrl = config('paysera.urls.accept_url') ?? '/';

        return redirect()->to($redirectUrl)->with('paysera_status', 'accepted');
    }

    public function cancel(Request $request): RedirectResponse|JsonResponse
    {
        if (config('paysera.api.enabled')) {
            return response()->json([
                'redirect_url' => config('paysera.urls.cancel_url'),
                'status' => 'cancelled',
            ]);
        }

        $redirectUrl = config('paysera.urls.cancel_url') ?? '/';

        return redirect()->to($redirectUrl)->with('paysera_status', 'cancelled');
    }
}
