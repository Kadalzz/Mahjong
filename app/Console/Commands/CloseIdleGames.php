<?php

namespace App\Console\Commands;

use App\Services\Poin\LobbyService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('poin:close-idle-games')]
#[Description('Finish point games and lobbies nobody has touched for longer than the configured idle limits, keeping finished rounds')]
class CloseIdleGames extends Command
{
    public function handle(LobbyService $lobby): int
    {
        foreach ($lobby->closeIdleGames() as $status => $count) {
            $this->info("Ditutup ({$status}): {$count}");
        }

        return self::SUCCESS;
    }
}
