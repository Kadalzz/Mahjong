<?php

namespace App\Services;

use App\Jobs\DeactivateTableDevice;
use App\Models\Booking;
use App\Models\MahjongTable;
use Illuminate\Support\Facades\Log;

class TableDeviceService
{
    public function activate(MahjongTable $table, Booking $booking): bool
    {
        if (empty($table->esp32_meja_id)) {
            return false;
        }

        $sent = $this->sendCommand("M{$table->esp32_meja_id},ON", $table);

        if ($sent) {
            DeactivateTableDevice::dispatch($booking)
                ->delay(now()->addHours($booking->duration_hours));
        }

        return $sent;
    }

    public function deactivate(MahjongTable $table, Booking $booking): bool
    {
        if (empty($table->esp32_meja_id)) {
            return false;
        }

        return $this->sendCommand("M{$table->esp32_meja_id},OFF", $table);
    }

    private function sendCommand(string $command, MahjongTable $table): bool
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
            return false;
        }

        stream_set_timeout($socket, $timeout);

        $payload = json_encode([
            'secret'  => config('esp32.bridge_secret'),
            'command' => $command,
        ]) . "\n";

        fwrite($socket, $payload);
        $response = trim((string) fgets($socket));
        fclose($socket);

        if ($response !== 'OK') {
            Log::error("ESP32 bridge rejected command for {$table->name} ({$command}): " . ($response ?: 'no response'));
            return false;
        }

        return true;
    }
}
