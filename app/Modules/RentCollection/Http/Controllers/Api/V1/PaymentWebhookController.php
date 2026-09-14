<?php

namespace App\Modules\RentCollection\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\RentCollection\Models\InvoicePaymentAttempt;
use App\Modules\RentCollection\Services\RentCollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The first inbound webhook endpoint in this app (see ARCHITECTURE.md
 * §10f). Deliberately does NOT trust the payload's claimed outcome -
 * it only uses the payload to find WHICH pending attempt to re-check,
 * then calls RentCollectionService::verifyAttempt(), which re-queries
 * the gateway's own status API with the company's real stored
 * credentials before ever marking anything successful. This sidesteps
 * needing a bespoke, precisely-correct signature-verification
 * implementation for each of the 12 payment drivers - a spoofed
 * request can, at worst, trigger a real (harmless) status re-check.
 */
class PaymentWebhookController extends Controller
{
    public function __construct(protected RentCollectionService $service)
    {
    }

    public function handle(Request $request, string $provider): JsonResponse
    {
        $reference = $this->extractReference($request);

        if (! $reference) {
            return response()->json(['message' => 'No reference found in payload.'], 422);
        }

        $attempt = InvoicePaymentAttempt::withoutGlobalScope('company')
            ->where('provider', $provider)
            ->where('reference', $reference)
            ->where('status', 'pending')
            ->first();

        if (! $attempt) {
            return response()->json(['message' => 'No matching pending attempt.']);
        }

        $this->service->verifyAttempt($attempt);

        return response()->json(['message' => 'Processed.']);
    }

    /**
     * Best-effort across this catalog's differing payload shapes - the
     * scheduled VerifyPendingRentPayments sweep (ARCHITECTURE.md §10f)
     * is the guaranteed fallback for any provider/shape not covered
     * here, so this doesn't need to be exhaustive to be useful.
     */
    protected function extractReference(Request $request): ?string
    {
        return $request->query('reference')
            ?? $request->input('reference')
            ?? $request->input('data.object.id')
            ?? $request->input('Body.stkCallback.CheckoutRequestID')
            ?? $request->input('id');
    }
}
