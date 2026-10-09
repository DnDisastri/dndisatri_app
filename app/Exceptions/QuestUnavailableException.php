<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class QuestUnavailableException extends RuntimeException
{
    public static function notActive(): self
    {
        return new self('Questa quest è già conclusa: non si può più modificare.');
    }
}
