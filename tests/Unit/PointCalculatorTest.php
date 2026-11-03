<?php

namespace Tests\Unit;

use App\Services\Poin\PointCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PointCalculatorTest extends TestCase
{
    private const PLAYERS = [11, 22, 33, 44];

    public static function everyResult(): array
    {
        $cases = [];

        foreach (self::PLAYERS as $winner) {
            $cases["P{$winner} dari deck"] = [$winner, null];

            foreach (self::PLAYERS as $discarder) {
                if ($discarder !== $winner) {
                    $cases["P{$winner} dari P{$discarder}"] = [$winner, $discarder];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('everyResult')]
    public function test_every_win_gives_the_agreed_points_and_sums_to_zero(int $winner, ?int $discarder): void
    {
        $points = (new PointCalculator())->calculate(self::PLAYERS, $winner, $discarder);

        $this->assertSame(self::PLAYERS, array_keys($points));
        $this->assertSame(0, array_sum($points));

        foreach ($points as $player => $value) {
            $expected = match (true) {
                $player === $winner    => $discarder === null ? 6 : 4,
                $discarder === null    => -2,
                $player === $discarder => -2,
                default                => -1,
            };

            $this->assertSame($expected, $value, "Pemain {$player}");
        }
    }

    public function test_there_are_sixteen_possible_results(): void
    {
        $this->assertCount(16, self::everyResult());
    }

    public function test_winner_must_sit_at_the_table(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PointCalculator())->calculate(self::PLAYERS, 99, null);
    }

    public function test_winner_cannot_discard_to_themselves(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PointCalculator())->calculate(self::PLAYERS, 11, 11);
    }

    public function test_discarder_must_sit_at_the_table(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PointCalculator())->calculate(self::PLAYERS, 11, 99);
    }

    public function test_a_round_needs_exactly_four_different_players(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PointCalculator())->calculate([11, 22, 33], 11, null);
    }

    public function test_duplicate_players_are_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PointCalculator())->calculate([11, 22, 33, 33], 11, null);
    }
}
