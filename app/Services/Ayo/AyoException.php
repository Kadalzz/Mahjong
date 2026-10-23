<?php

namespace App\Services\Ayo;

use RuntimeException;

class AyoException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 0)
    {
        parent::__construct($message, $status);
    }

    public function isConflict(): bool
    {
        return $this->status === 403;
    }

    public function isTransient(): bool
    {
        return $this->status === 0 || $this->status === 429 || $this->status >= 500;
    }
}
