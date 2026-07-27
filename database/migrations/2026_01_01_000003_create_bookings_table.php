<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahjong_table_id')->constrained('mahjong_tables')->onDelete('cascade');
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->date('booking_date');
            $table->time('start_time');
            $table->integer('duration_hours')->default(1);
            $table->time('end_time');
            $table->decimal('total_price', 12, 2);
            $table->enum('status', [
                'pending_payment',
                'waiting',
                'active',
                'done',
                'cancelled'
            ])->default('pending_payment');
            $table->string('booking_code')->unique();
            $table->string('midtrans_order_id')->nullable()->unique();
            $table->string('midtrans_payment_url')->nullable();
            $table->string('midtrans_token')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
