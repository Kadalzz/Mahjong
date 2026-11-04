<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = ['winner_player_id', 'discarder_player_id', 'recorded_by_player_id'];

    public function up(): void
    {
        $this->rebuild(fn ($foreign) => $foreign->restrictOnDelete());
    }

    public function down(): void
    {
        $this->rebuild(fn ($foreign) => $foreign->cascadeOnDelete());
    }

    private function rebuild(Closure $onDelete): void
    {
        Schema::table('game_rounds', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                $table->dropForeign([$column]);
            }
        });

        Schema::table('game_rounds', function (Blueprint $table) use ($onDelete) {
            foreach (self::COLUMNS as $column) {
                $onDelete($table->foreign($column)->references('id')->on('players'));
            }
        });
    }
};
