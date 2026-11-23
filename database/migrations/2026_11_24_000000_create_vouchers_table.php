<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('label', 60);
            $table->string('discount_type', 10);
            $table->unsignedInteger('discount_value');
            $table->unsignedInteger('max_discount')->nullable();
            $table->date('expires_on')->nullable();
            $table->foreignId('booking_order_id')->nullable()->constrained('booking_orders')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('label');
        });

        Schema::table('booking_orders', function (Blueprint $table) {
            $table->string('voucher_code', 20)->nullable()->after('total_price');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('voucher_code');
        });
    }

    public function down(): void
    {
        Schema::table('booking_orders', function (Blueprint $table) {
            $table->dropColumn(['voucher_code', 'discount_amount']);
        });

        Schema::dropIfExists('vouchers');
    }
};
