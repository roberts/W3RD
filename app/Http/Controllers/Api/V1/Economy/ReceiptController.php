<?php

namespace App\Http\Controllers\Api\V1\Economy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Economy\VerifyReceiptRequest;
use App\Http\Traits\ApiResponses;
use App\Services\ReceiptVerificationService;
use Illuminate\Http\JsonResponse;

class ReceiptController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected ReceiptVerificationService $verificationService
    ) {}

    /**
     * Verify platform-specific receipt (Apple, Google, Telegram).
     */
    public function store(VerifyReceiptRequest $request, string $provider): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->handleServiceCall(
            fn () => $this->verificationService->verify(
                provider: $provider,
                receipt: $validated['receipt'],
                userId: $request->user()->id
            ),
            'Failed to verify receipt'
        );

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return $this->dataResponse([
            'verified' => $result['verified'] ?? false,
            'subscription_status' => $result['subscription_status'] ?? null,
            'expires_at' => $result['expires_at'] ?? null,
        ]);
    }
}
