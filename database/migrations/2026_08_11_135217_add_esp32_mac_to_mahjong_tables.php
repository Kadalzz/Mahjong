<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahjong_tables', function (Blueprint $table) {
            // Integer table ID used to address this table's ESP32 Client
            // through the ESP32 Master (e.g. "M3,ON"). The MAC pairing for
            // ESP-NOW itself lives in the Master's firmware (clientMAC[]),
            // not here - this column only maps our table to that ID.
            $table->unsignedTinyInteger('esp32_meja_id')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('mahjong_tables', function (Blueprint $table) {
            $table->dropColumn('esp32_meja_id');
        });
    }
};
