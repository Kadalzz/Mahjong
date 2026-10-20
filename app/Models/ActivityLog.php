<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    public const ACTIONS = [
        'pause_start'    => 'Jeda dimulai',
        'pause_stop'     => 'Jeda dihentikan',
        'account_create' => 'Akun dibuat',
        'account_update' => 'Akun diubah',
        'account_toggle' => 'Status akun diubah',
    ];

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'mahjong_table_id',
        'table_name',
        'minutes',
        'detail',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public static function record(User $user, string $action, ?MahjongTable $table = null, ?int $minutes = null, ?string $detail = null): self
    {
        return self::create([
            'user_id'          => $user->id,
            'user_name'        => $user->name,
            'action'           => $action,
            'mahjong_table_id' => $table?->id,
            'table_name'       => $table?->name,
            'minutes'          => $minutes,
            'detail'           => $detail,
        ]);
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }
}
