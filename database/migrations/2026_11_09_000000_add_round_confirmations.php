<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_rounds', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('recorded_by_player_id');
        });

        DB::table('game_rounds')->update(['confirmed_at' => DB::raw('created_at')]);

        Schema::create('game_round_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_round_id')->constrained('game_rounds')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->timestamp('seen_at')->nullable();
            $table->timestamp('agreed_at')->nullable();
            $table->timestamps();
            $table->unique(['game_round_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_round_confirmations');

        Schema::table('game_rounds', function (Blueprint $table) {
            $table->dropColumn('confirmed_at');
        });
    }
};
