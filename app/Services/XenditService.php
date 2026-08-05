<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XenditService
{
    /**
     * Create a Xendit hosted-checkout invoice for a booking.
     *
     * @return array{order_id: string, invoice_url: string}|null
     */
    public function createInvoice(Booking $booking, string $orderId): ?array
    {
        $secretKey = config('xendit.secret_key');

        if (empty($secretKey)) {
            Log::warning("Xendit invoice not created for {$booking->booking_code}: XENDIT_SECRET_KEY is not configured.");
            return null;
        }

        try {
            $response = Http::withBasicAuth($secretKey, '')
                ->post(config('xendit.api_url') . '/v2/invoices', [
                    'external_id'          => $orderId,
                    'amount'                => (int) $booking->total_price,
                    'currency'              => 'IDR',
                    'description'           => "Reservasi {$booking->table->name} ({$booking->duration_hours} jam)",
                    'invoice_duration'      => 3600,
                    'customer' => [
                        'given_names'   => $booking->customer_name,
                        'mobile_number' => $this->formatPhone($booking->customer_phone),
                    ],
                    'success_redirect_url' => route('booking.confirm', $booking->booking_code),
                    'failure_redirect_url' => route('booking.confirm', $booking->booking_code),
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("Xendit connection failed for {$booking->booking_code}: " . $e->getMessage());
            return null;
        }

        if (!$response->successful()) {
            Log::error("Xendit error for {$booking->booking_code}: " . $response->body());
            return null;
        }

        $data = $response->json();

        return [
            'order_id'    => $orderId,
            'invoice_url' => $data['invoice_url'],
        ];
    }

    private function formatPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (!str_starts_with($digits, '62')) {
            $digits = '62' . $digits;
        }

        return '+' . $digits;
    }
}
