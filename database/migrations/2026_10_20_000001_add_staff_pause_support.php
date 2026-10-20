<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_minutes')->nullable()->after('duration_hours');
            $table->foreignId('created_by')->nullable()->after('source')->constrained('users')->nullOnDelete();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name');
            $table->string('action');
            $table->foreignId('mahjong_table_id')->nullable()->constrained('mahjong_tables')->nullOnDelete();
            $table->string('table_name')->nullable();
            $table->unsignedSmallInteger('minutes')->nullable();
            $table->string('detail')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('duration_minutes');
        });
    }
};
