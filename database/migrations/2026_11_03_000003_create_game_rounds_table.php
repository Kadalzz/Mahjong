<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->foreignId('winner_player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('discarder_player_id')->nullable()->constrained('players')->cascadeOnDelete();
            $table->foreignId('recorded_by_player_id')->constrained('players')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['game_id', 'number']);
        });

        Schema::table('player_round_scores', function (Blueprint $table) {
            $table->foreignId('game_round_id')->nullable()->after('player_id')->constrained('game_rounds')->cascadeOnDelete();
        });

        Schema::table('game_players', function (Blueprint $table) {
            $table->unsignedInteger('seen_round')->default(0)->after('is_ready');
        });
    }

    public function down(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            $table->dropColumn('seen_round');
        });

        Schema::table('player_round_scores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('game_round_id');
        });

        Schema::dropIfExists('game_rounds');
    }
};
