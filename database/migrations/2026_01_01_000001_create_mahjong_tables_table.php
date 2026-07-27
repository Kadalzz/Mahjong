<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahjong_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name');          // e.g. "Meja 1"
            $table->integer('capacity')->default(4); // 4 players per table
            $table->enum('status', ['available', 'occupied', 'maintenance'])->default('available');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mahjong_tables');
    }
};
