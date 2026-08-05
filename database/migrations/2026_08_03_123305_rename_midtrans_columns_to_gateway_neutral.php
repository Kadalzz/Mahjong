<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['midtrans_order_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['midtrans_order_id', 'midtrans_payment_url', 'midtrans_token']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('payment_order_id')->nullable()->unique()->after('booking_code');
            $table->string('payment_url')->nullable()->after('payment_order_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['midtrans_transaction_id', 'midtrans_status', 'midtrans_payload']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('gateway_transaction_id')->nullable()->after('payment_method');
            $table->string('gateway_status')->nullable()->after('gateway_transaction_id');
            $table->json('gateway_payload')->nullable()->after('gateway_status');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['payment_order_id', 'payment_url']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('midtrans_order_id')->nullable()->unique();
            $table->string('midtrans_payment_url')->nullable();
            $table->string('midtrans_token')->nullable();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['gateway_transaction_id', 'gateway_status', 'gateway_payload']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('midtrans_transaction_id')->nullable();
            $table->string('midtrans_status')->nullable();
            $table->json('midtrans_payload')->nullable();
        });
    }
};
