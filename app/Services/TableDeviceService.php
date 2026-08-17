<?php

namespace App\Services;

use App\Jobs\DeactivateTableDevice;
use App\Models\Booking;
use App\Models\MahjongTable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TableDeviceService
{
    public function activate(MahjongTable $table, Booking $booking): bool
    {
        $sent = $this->turnOn($table);

        if ($sent) {
            DeactivateTableDevice::dispatch($booking)
                ->delay(now()->addHours($booking->duration_hours));
        }

        return $sent;
    }

    public function deactivate(MahjongTable $table, Booking $booking): bool
    {
        return $this->turnOff($table);
    }

    /**
     * Manual override (no booking involved) - used by the admin "on/off"
     * button in Kelola Meja as a fallback when the automatic signal
     * (e.g. from the payment webhook) fails to reach the ESP32 Master.
     */
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