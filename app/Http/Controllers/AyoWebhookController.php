<?php

namespace App\Http\Controllers;

use App\Services\Ayo\AyoException;
use App\Services\Ayo\AyoSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AyoWebhookController extends Controller
{
    public function __invoke(Request $request, AyoSyncService $ayo)
    {
        $key = (string) config('ayo.webhook_key');

        if (!$ayo->enabled() || $key === '' || !hash_equals($key, (string) $request->query('key'))) {
            return response()->json(['error' => true, 'status_code' => 401, 'message' => 'Unauthorized'], 401);
        }

        $payload = $request->json()->all();
        $items = array_is_list($payload) ? $payload : [$payload];

        $bookingIds = collect($items)->pluck('booking_id')->filter()->unique();

        try {
            foreach ($bookingIds as $bookingId) {
                $ayo->refreshByBookingId((string) $bookingId);
            }
        } catch (AyoException $e) {
            Log::error('AYO webhook could not confirm booking: ' . $e->getMessage());

            return response()->json(['error' => true, 'status_code' => 500, 'message' => 'Retry later'], 500);
        }

        return response()->json(['error' => false, 'status_code' => 200, 'message' => 'Success']);
    }
}
