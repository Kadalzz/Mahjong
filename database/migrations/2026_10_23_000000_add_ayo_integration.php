<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahjong_tables', function (Blueprint $table) {
            $table->unsignedBigInteger('ayo_field_id')->nullable()->unique()->after('esp32_meja_id');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('ayo_order_detail_id')->nullable()->unique()->after('created_by');
            $table->string('ayo_status')->nullable()->after('ayo_order_detail_id');
            $table->boolean('ayo_conflict')->default(false)->after('ayo_status');
        });

        Schema::create('ayo_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->unsignedBigInteger('field_id');
            $table->date('date');
            $table->string('start_time', 8);
            $table->string('end_time', 8);
            $table->unsignedInteger('total_price')->default(0);
            $table->unsignedBigInteger('order_detail_id')->nullable()->unique();
            $table->string('ayo_booking_id')->nullable();
            $table->string('status')->default('pushing');
            $table->unsignedInteger('attempts')->default(0);
            $table->string('last_error')->nullable();
            $table->timestamps();

            $table->index(['status']);
            $table->index(['field_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ayo_reservations');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['ayo_order_detail_id']);
            $table->dropColumn(['ayo_order_detail_id', 'ayo_status', 'ayo_conflict']);
        });

        Schema::table('mahjong_tables', function (Blueprint $table) {
            $table->dropUnique(['ayo_field_id']);
            $table->dropColumn('ayo_field_id');
        });
    }
};
