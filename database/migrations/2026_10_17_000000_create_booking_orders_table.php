<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('source')->default('customer');
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->date('booking_date');
            $table->decimal('total_price', 12, 2)->default(0);
            $table->string('status')->default('pending_payment');
            $table->string('refund_status')->nullable();
            $table->string('payment_order_id')->nullable()->unique();
            $table->string('payment_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('booking_order_id')->nullable()->after('id')->constrained('booking_orders')->cascadeOnDelete();
            $table->string('source')->default('customer')->after('booking_order_id');
        });

        DB::table('bookings')->orderBy('id')->each(function ($booking) {
            $orderId = DB::table('booking_orders')->insertGetId([
                'code'             => $booking->booking_code,
                'source'           => 'customer',
                'customer_name'    => $booking->customer_name,
                'customer_phone'   => $booking->customer_phone,
                'booking_date'     => substr($booking->booking_date, 0, 10),
                'total_price'      => $booking->total_price,
                'status'           => $booking->status === 'waiting' ? 'cancelled' : $booking->status,
                'refund_status'    => $booking->refund_status,
                'payment_order_id' => $booking->payment_order_id,
                'payment_url'      => $booking->payment_url,
                'created_at'       => $booking->created_at,
                'updated_at'       => $booking->updated_at,
            ]);

            DB::table('bookings')->where('id', $booking->id)->update(['booking_order_id' => $orderId]);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_order_id');
            $table->dropColumn('source');
        });

        Schema::dropIfExists('booking_orders');
    }
};
