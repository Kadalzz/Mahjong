<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dateTime('starts_at')->nullable()->after('end_time');
            $table->dateTime('ends_at')->nullable()->after('starts_at');
            $table->index(['mahjong_table_id', 'starts_at', 'ends_at']);
        });

        DB::table('bookings')->orderBy('id')->each(function ($booking) {
            $startsAt = Carbon::parse(substr($booking->booking_date, 0, 10) . ' ' . substr($booking->start_time, 0, 5));

            DB::table('bookings')->where('id', $booking->id)->update([
                'starts_at' => $startsAt,
                'ends_at'   => $startsAt->copy()->addHours((int) $booking->duration_hours),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['mahjong_table_id', 'starts_at', 'ends_at']);
            $table->dropColumn(['starts_at', 'ends_at']);
        });
    }
};
