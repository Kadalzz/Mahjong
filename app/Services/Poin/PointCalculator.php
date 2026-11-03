<?php

namespace App\Services\Poin;

use InvalidArgumentException;

class PointCalculator
{
    public const PLAYERS = 4;

    public const WIN_FROM_PLAYER = 4;
    public const DISCARDER = -2;
    public const OTHER_FROM_PLAYER = -1;

    public const WIN_FROM_DECK = 6;
    public const OTHER_FROM_DECK = -2;

    public function calculate(array $players, int $winner, ?int $discarder = null): array
    {
        $players = array_values($players);

        if (count($players) !== self::PLAYERS || count(array_unique($players)) !== self::PLAYERS) {
            throw new InvalidArgumentException('Satu ronde harus dimainkan tepat 4 pemain berbeda.');
        }

        if (!in_array($winner, $players, true)) {
            throw new InvalidArgumentException('Pemenang tidak ada di meja.');
        }

        if ($discarder !== null && ($discarder === $winner || !in_array($discarder, $players, true))) {
            throw new InvalidArgumentException('Pemain pengeluar tile tidak valid.');
        }

        $points = [];

        foreach ($players as $player) {
            $points[$player] = match (true) {
                $player === $winner    => $discarder === null ? self::WIN_FROM_DECK : self::WIN_FROM_PLAYER,
                $discarder === null    => self::OTHER_FROM_DECK,
                $player === $discarder => self::DISCARDER,
                default                => self::OTHER_FROM_PLAYER,
            };
        }

        return $points;
    }
}
