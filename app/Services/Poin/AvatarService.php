<?php

namespace App\Services\Poin;

use App\Models\Player;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AvatarService
{
    public function store(Player $player, UploadedFile $file): void
    {
        $extension = $file->guessExtension() === 'png' ? 'png' : 'jpg';
        $path = $file->storeAs('avatars', Str::random(40) . '.' . $extension, Player::AVATAR_DISK);

        $this->deleteFiles($player->avatar_path, $player->avatar_previous_path);
        $player->forceFill(['avatar_path' => $path, 'avatar_previous_path' => null])->save();
    }

    public function remove(Player $player): bool
    {
        if (!$player->avatar_path) {
            return false;
        }

        $this->deleteFiles($player->avatar_previous_path);
        $player->forceFill(['avatar_previous_path' => $player->avatar_path, 'avatar_path' => null])->save();

        return true;
    }

    public function restore(Player $player): bool
    {
        $previous = $player->avatar_previous_path;

        if (!$previous || $player->avatar_path || !Storage::disk(Player::AVATAR_DISK)->exists($previous)) {
            return false;
        }

        $player->forceFill(['avatar_path' => $previous, 'avatar_previous_path' => null])->save();

        return true;
    }

    private function deleteFiles(?string ...$paths): void
    {
        $paths = array_filter($paths);

        if ($paths) {
            Storage::disk(Player::AVATAR_DISK)->delete($paths);
        }
    }
}
