<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahjong_tables', function (Blueprint $table) {
            
            
            
            
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
