<?php

declare(strict_types=1);

namespace App\Enums;

/** Con quali regole è fatto un personaggio; le righe dei cataloghi nuovi la portano anche loro. */
enum RulesEdition: string
{
    case Classic = '2014';
    case Revised = '2024';

    public function label(): string
    {
        return match ($this) {
            self::Classic => '5e (2014)',
            self::Revised => '5.5e (2024)',
        };
    }

    /** Le regole 2024 arrivano più avanti: per ora si crea solo in 2014. */
    public function isCreatable(): bool
    {
        return $this === self::Classic;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $e) => [$e->value => $e->label()])->all();
    }
}
