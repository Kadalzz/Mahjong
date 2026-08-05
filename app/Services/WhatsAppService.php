<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send a payment invoice to the customer's WhatsApp via Fonnte.
     */
    public function sendInvoice(Booking $booking): bool
    {
        $token = config('fonnte.token');

        if (empty($token)) {
            Log::warning("WhatsApp invoice not sent for {$booking->booking_code}: FONNTE_TOKEN is not configured.");
            return false;
        }

        try {
            $response = Http::withHeaders(['Authorization' => $token])
                ->asForm()
                ->post(config('fonnte.endpoint'), [
                    'target'  => $this->formatPhone($booking->customer_phone),
                    'message' => $this->buildInvoiceMessage($booking),
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("WhatsApp connection failed for {$booking->booking_code}: " . $e->getMessage());
            return false;
        }

        if (!$response->successful() || ($response->json('status') === false)) {
            Log::error("WhatsApp invoice failed for {$booking->booking_code}: " . $response->body());
            return false;
        }

        return true;
    }

    /**
     * Fonnte expects Indonesian numbers in 62xxxxxxxxxx format (no +, no leading 0).
     */
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

    private function buildInvoiceMessage(Booking $booking): string
    {
        $booking->loadMissing('table', 'transaction');

        $paymentMethod = $booking->transaction->payment_method ?? 'Transfer';
        $paidAt        = $booking->transaction?->paid_at?->translatedFormat('d F Y, H:i') ?? now()->translatedFormat('d F Y, H:i');

        return <<<MSG
        🀄 *HÓNG ZHŌNG MAHJONG* — Invoice Pembayaran

        No. Invoice: *{$booking->booking_code}*
        Dibayar: {$paidAt}

        Halo *{$booking->customer_name}*, pembayaran Anda telah kami terima ✅

        *Detail Reservasi*
        Meja: {$booking->table->name}
        Tanggal: {$booking->booking_date->translatedFormat('l, d F Y')}
        Waktu: {$this->shortTime($booking->start_time)} – {$this->shortTime($booking->end_time)} ({$booking->duration_hours} jam)
        Metode Bayar: {$paymentMethod}

        *Total Bayar: Rp {$this->rupiah($booking->total_price)}*

        Terima kasih telah reservasi di Hóng Zhōng Mahjong! Sampai jumpa 🀄
        MSG;
    }

    private function shortTime(string $time): string
    {
        return substr($time, 0, 5);
    }

    private function rupiah(float $amount): string
    {
        return number_format($amount, 0, ',', '.');
    }
}
