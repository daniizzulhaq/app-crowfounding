<?php

namespace App\Http\Controllers;

use App\Actions\Donations\ApplyPaymentNotification;
use App\Actions\Donations\InvalidSignatureException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request, ApplyPaymentNotification $action): JsonResponse
    {
        $payload = $request->json()->all() ?: $request->all();

        try {
            $donation = $action->handle($payload);
        } catch (InvalidSignatureException) {
            Log::warning('Midtrans webhook: signature tidak valid', ['order_id' => $payload['order_id'] ?? null, 'ip' => $request->ip()]);

            return response()->json(['message' => 'Invalid signature'], 403);
        } catch (ModelNotFoundException) {
            // Unknown order (e.g. Midtrans test ping). 200 so Midtrans stops retrying.
            return response()->json(['message' => 'Order not found'], 200);
        }

        return response()->json(['message' => 'OK', 'status' => $donation->status->value]);
    }
}
