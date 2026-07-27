<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pricing extends Model
{
    protected $table = 'pricing';
    protected $fillable = ['mahjong_table_id', 'price_per_hour', 'is_active'];

    public function table(): BelongsTo
    {
        return $this->belongsTo(MahjongTable::class, 'mahjong_table_id');
    }
}
