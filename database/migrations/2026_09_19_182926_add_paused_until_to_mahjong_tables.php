<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mahjong_tables', function (Blueprint $table) {
            // Set together with status='maintenance' when admin uses a quick
            // "jeda" (cleanup pause) button. Distinguishes an auto-expiring
            // pause from a manually-set, indefinite maintenance status.
            $table->timestamp('paused_until')->nullable()->after('esp32_meja_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mahjong_tables', function (Blueprint $table) {
            $table->dropColumn('paused_until');
        });
    }
};
