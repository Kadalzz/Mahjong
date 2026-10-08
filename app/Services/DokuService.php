<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DokuService
{
    public const NOTIFICATION_PATH = '/doku/webhook';

    private const PAYMENT_PATH = '/checkout/v1/payment';

    public function createInvoice(Booking $booking, string $orderId): ?array
    {
        $clientId = config('doku.client_id');
        $secretKey = config('doku.secret_key');

        if (empty($clientId) || empty($secretKey)) {
            Log::warning("DOKU payment not created for {$booking->booking_code}: DOKU_CLIENT_ID/DOKU_SECRET_KEY is not configured.");
            return null;
        }

        $bodyJson = json_encode([
            'order' => [
                'amount'              => (int) $booking->total_price,
                'invoice_number'      => $orderId,
                'currency'            => 'IDR',
                'callback_url'        => route('booking.confirm', $booking->booking_code),
                'callback_url_cancel' => route('booking.confirm', $booking->booking_code),
                'callback_url_result' => route('booking.confirm', $booking->booking_code),
                'auto_redirect'       => true,
                'line_items' => [[
                    'id'       => (string) $booking->mahjong_table_id,
                    'name'     => "Reservasi {$booking->table->name} ({$booking->duration_hours} jam)",
                    'quantity' => 1,
                    'price'    => (int) $booking->total_price,
                ]],
            ],
            'payment' => [
                'payment_due_date' => (int) config('doku.payment_due_minutes', 60),
            ],
            'customer' => [
                'name'  => $booking->customer_name,
                'phone' => $this->formatPhone($booking->customer_phone),
            ],
        ]);

        $headers = $this->buildRequestHeaders(self::PAYMENT_PATH, $bodyJson);

        try {
            $response = Http::withHeaders($headers)
                ->withBody($bodyJson, 'application/json')
                ->post($this->baseUrl() . self::PAYMENT_PATH);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("DOKU connection failed for {$booking->booking_code}: " . $e->getMessage());
            return null;
        }

        if (!$response->successful()) {
            Log::error("DOKU error for {$booking->booking_code}: " . $response->body());
            return null;
        }

        $url = $response->json('response.payment.url');

        if (empty($url)) {
            Log::error("DOKU response missing payment url for {$booking->booking_code}: " . $response->body());
            return null;
        }

        return [
            'order_id'    => $orderId,
            'invoice_url' => $url,
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $secretKey = config('doku.secret_key');

        if (empty($secretKey)) {
            return false;
        }

        $clientId  = $request->header('Client-Id');
        $requestId = $request->header('Request-Id');
        $timestamp = $request->header('Request-Timestamp');
        $signature = $request->header('Signature');

        if (empty($clientId) || empty($requestId) || empty($timestamp) || empty($signature)) {
            return false;
        }

        $digest = base64_encode(hash('sha256', $request->getContent(), true));

        $stringToSign = "Client-Id:{$clientId}\n"
            . "Request-Id:{$requestId}\n"
            . "Request-Timestamp:{$timestamp}\n"
            . "Request-Target:" . self::NOTIFICATION_PATH . "\n"
            . "Digest:{$digest}";

        $expected = 'HMACSHA256=' . base64_encode(hash_hmac('sha256', $stringToSign, $secretKey, true));

        return hash_equals($expected, $signature);
    }

    private function buildRequestHeaders(string $path, string $rawBody): array
    {
        $clientId  = config('doku.client_id');
        $secretKey = config('doku.secret_key');
        $requestId = (string) Str::uuid();
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $digest    = base64_encode(hash('sha256', $rawBody, true));

        $stringToSign = "Client-Id:{$clientId}\n"
            . "Request-Id:{$requestId}\n"
            . "Request-Timestamp:{$timestamp}\n"
            . "Request-Target:{$path}\n"
            . "Digest:{$digest}";

        $signature = 'HMACSHA256=' . base64_encode(hash_hmac('sha256', $stringToSign, $secretKey, true));

        return [
            'Client-Id'         => $clientId,
            'Request-Id'        => $requestId,
            'Request-Timestamp' => $timestamp,
            'Signature'         => $signature,
        ];
    }

    private function baseUrl(): string
    {
        return config('doku.is_production')
            ? 'https://api.doku.com'
            : 'https://api-sandbox.doku.com';
    }

    private function formatPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (!str_starts_with($digits, '62')) {
            $digits = '62' . $digits;
        }

        return $digits;
    }
}
