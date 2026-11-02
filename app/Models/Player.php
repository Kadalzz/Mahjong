<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Player extends Authenticatable
{
    public const SESSION_FINGERPRINT = 'player_password_fingerprint';

    protected $fillable = ['nickname', 'email', 'password'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    protected static function booted(): void
    {
        static::saving(function (Player $player) {
            $player->nickname_key = self::nicknameKey((string) $player->nickname);
            $player->email = mb_strtolower((string) $player->email);
        });
    }

    public static function nicknameKey(string $nickname): string
    {
        return mb_strtolower(trim($nickname));
    }

    public function passwordFingerprint(): string
    {
        return hash_hmac('sha256', (string) $this->password, (string) config('app.key'));
    }
}
