<?php

namespace App\Services\Poin;

use App\Models\Player;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AvatarService
{
    public function store(Player $player, UploadedFile $file): bool
    {
        $png = $file->guessExtension() === 'png';
        $binary = $this->reencode((string) file_get_contents($file->getRealPath()), $png, $file->getRealPath());

        if ($binary === null) {
            return false;
        }

        $path = 'avatars/' . Str::random(40) . ($png ? '.png' : '.jpg');
        Storage::disk(Player::AVATAR_DISK)->put($path, $binary);

        $this->deleteFiles($player->avatar_path, $player->avatar_previous_path);
        $player->forceFill(['avatar_path' => $path, 'avatar_previous_path' => null])->save();

        return true;
    }

    private function reencode(string $contents, bool $png, string $source): ?string
    {
        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            return null;
        }

        $image = $this->orient($image, $png ? 1 : $this->orientation($source));
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, Player::AVATAR_SIDE / max($width, $height));
        $target = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));

        if ($png) {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
        }

        imagecopyresampled($target, $image, 0, 0, 0, 0, imagesx($target), imagesy($target), $width, $height);
        imagedestroy($image);

        ob_start();
        $png ? imagepng($target, null, 6) : imagejpeg($target, null, 85);
        imagedestroy($target);

        return ob_get_clean() ?: null;
    }

    private function orientation(string $source): int
    {
        if (!function_exists('exif_read_data')) {
            return 1;
        }

        $exif = @exif_read_data($source);

        return (int) ($exif['Orientation'] ?? 1);
    }

    private function orient(\GdImage $image, int $orientation): \GdImage
    {
        $rotated = match ($orientation) {
            3       => imagerotate($image, 180, 0),
            6       => imagerotate($image, -90, 0),
            8       => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated === null || $rotated === false) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
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
