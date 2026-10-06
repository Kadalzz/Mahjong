<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('mahjong_tables', function (Blueprint $table) {
            
            
            
            $table->timestamp('paused_until')->nullable()->after('esp32_meja_id');
        });
    }

    
    public function down(): void
    {
        Schema::table('mahjong_tables', function (Blueprint $table) {
            $table->dropColumn('paused_until');
        });
    }
};
