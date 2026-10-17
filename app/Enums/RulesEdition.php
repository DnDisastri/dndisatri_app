<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\AppSetting;
use App\Models\User;

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

    /**
     * Il 2014 sempre. Il 2024 i DM lo possono provare sempre, i giocatori solo
     * quando un admin lo apre dalle Impostazioni del pannello.
     */
    public function isCreatableBy(?User $user): bool
    {
        return $this === self::Classic
            || ($user?->isDm() ?? false)
            || (bool) AppSetting::get(AppSetting::RULES_2024_OPEN, false);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $e) => [$e->value => $e->label()])->all();
    }
}
