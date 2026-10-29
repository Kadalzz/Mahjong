<?php

namespace App\Services\Ayo;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class AyoClient
{
    public function isConfigured(): bool
    {
        return config('ayo.token') !== '' && config('ayo.private_key') !== '' && config('ayo.venue_code') !== '';
    }

    public function sign(array $payload): string
    {
        unset($payload['signature']);
        ksort($payload);

        return hash_hmac('sha512', http_build_query($payload), (string) config('ayo.private_key'));
    }

    public function verifyResponse(array $json): bool
    {
        $data = $json['data'] ?? null;
        $signature = (string) ($json['signature'] ?? '');

        if (!is_array($data) || $signature === '') {
            return false;
        }

        ksort($data);

        return hash_equals(hash_hmac('sha512', http_build_query($data), (string) config('ayo.private_key')), $signature);
    }

    public function listFields(): array
    {
        return $this->request('GET', 'list-fields', []);
    }

    public function listBookings(array $filters): array
    {
        return $this->request('GET', 'list-bookings/' . config('ayo.venue_code'), $filters);
    }

    public function createReservation(array $reservation): array
    {
        return $this->request('POST', 'create-reservation', $reservation);
    }

    public function cancelReservation(int $orderDetailId): array
    {
        return $this->request('POST', 'cancel-reservation', ['order_detail_id' => $orderDetailId], 30);
    }

    private function request(string $method, string $path, array $payload, ?int $timeout = null): array
    {
        if (!$this->isConfigured()) {
            throw new AyoException('Kredensial AYO belum diisi.');
        }

        $payload['token'] = (string) config('ayo.token');
        $payload['signature'] = $this->sign($payload);

        try {
            $response = Http::acceptJson()
                ->timeout($timeout ?? (int) config('ayo.timeout', 8))
                ->withBody(json_encode($payload), 'application/json')
                ->send($method, config('ayo.base_url') . '/' . $path);
        } catch (ConnectionException $e) {
            throw new AyoException('Tidak bisa terhubung ke AYO: ' . $e->getMessage());
        }

        $json = $response->json() ?? [];

        if (!$response->successful() || ($json['error'] ?? false)) {
            throw new AyoException((string) ($json['message'] ?? 'AYO menolak permintaan.'), $response->status());
        }

        if (!$this->verifyResponse($json)) {
            throw new AyoException('Tanda tangan jawaban AYO tidak valid.', 502);
        }

        return $json['data'];
    }
}
