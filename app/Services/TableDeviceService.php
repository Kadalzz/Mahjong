<?php

namespace App\Services;

use App\Jobs\ActivateTableDevice;
use App\Jobs\DeactivateTableDevice;
use App\Models\Booking;
use App\Models\MahjongTable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TableDeviceService
{
    public function activate(MahjongTable $table, Booking $booking): bool
    {
        if ($booking->source === 'block') {
            return false;
        }

        $startsAt = $booking->startsAt();
        $endsAt   = $booking->endsAt();

        if (now()->gte($endsAt)) {
            return false;
        }

        if (now()->lt($startsAt)) {
            ActivateTableDevice::dispatch($booking)->delay($startsAt);
            return true;
        }

        $sent = $this->turnOn($table);

        DeactivateTableDevice::dispatch($booking)->delay($endsAt);

        return $sent;
    }

    public function deactivate(MahjongTable $table, Booking $booking): bool
    {
        return $this->turnOff($table);
    }

    
    public function turnOn(MahjongTable $table): bool
    {
        if (empty($table->esp32_meja_id)) {
            return false;
        }

        return $this->sendCommand("M{$table->esp32_meja_id},ON", $table);
    }

    public function turnOff(MahjongTable $table): bool
    {
        if (empty($table->esp32_meja_id)) {
            return false;
        }

        return $this->sendCommand("M{$table->esp32_meja_id},OFF", $table);
    }

    private function sendCommand(string $command, MahjongTable $table): bool
    {
        $payload = json_encode([
            'secret'  => config('esp32.bridge_secret'),
            'command' => $command,
        ]);

        $response = config('esp32.bridge_mode') === 'http'
            ? $this->sendViaHttp($payload, $table, $command)
            : $this->sendViaTcp($payload, $table, $command);

        if ($response !== 'OK') {
            Log::error("ESP32 bridge rejected command for {$table->name} ({$command}): " . ($response ?: 'no response'));
            return false;
        }

        return true;
    }

    private function sendViaTcp(string $payload, MahjongTable $table, string $command): ?string
    {
        $host = config('esp32.bridge_host');
        $port = config('esp32.bridge_port');
        $timeout = config('esp32.timeout');

        $socket = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            $timeout
        );

        if ($socket === false) {
            Log::error("ESP32 bridge unreachable for {$table->name} ({$command}): {$errstr}");
            return null;
        }

        stream_set_timeout($socket, $timeout);
        fwrite($socket, $payload . "\n");
        $response = trim((string) fgets($socket));
        fclose($socket);

        return $response;
    }

    private function sendViaHttp(string $payload, MahjongTable $table, string $command): ?string
    {
        $url = config('esp32.bridge_http_url');
        $timeout = config('esp32.timeout');

        try {
            $response = Http::timeout($timeout)
                ->withBody($payload, 'application/json')
                ->post($url);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("ESP32 bridge (http) unreachable for {$table->name} ({$command}): " . $e->getMessage());
            return null;
        }

        return trim($response->body());
    }
} 