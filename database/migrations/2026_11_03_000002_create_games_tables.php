<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahjong_tables', function (Blueprint $table) {
            $table->string('qr_code', 12)->nullable()->unique()->after('esp32_meja_id');
        });

        foreach (DB::table('mahjong_tables')->pluck('id') as $id) {
            DB::table('mahjong_tables')->where('id', $id)->update(['qr_code' => Str::upper(Str::random(8))]);
        }

        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahjong_table_id')->constrained('mahjong_tables')->cascadeOnDelete();
            $table->string('status', 10)->default('lobby')->index();
            $table->unsignedInteger('current_round')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('game_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->unsignedTinyInteger('seat');
            $table->boolean('is_ready')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();
            $table->unique(['game_id', 'seat']);
            $table->unique(['game_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_players');
        Schema::dropIfExists('games');

        Schema::table('mahjong_tables', function (Blueprint $table) {
            $table->dropUnique(['qr_code']);
            $table->dropColumn('qr_code');
        });
    }
};
