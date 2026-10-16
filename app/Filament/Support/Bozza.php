<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Model;

/**
 * L'interruttore «Bozza» di sessioni e quest: diventa `published_at`, la data
 * della prima pubblicazione. Pubblicata non torna bozza: chi l'ha vista o
 * prenotata la perderebbe.
 */
final class Bozza
{
    public static function toggle(string $aiGiocatori): Toggle
    {
        return Toggle::make('bozza')
            ->label('Bozza')
            ->helperText($aiGiocatori)
            ->dehydrated(false)
            ->visible(fn (?Model $record) => $record === null || $record->published_at === null)
            ->afterStateHydrated(fn (Toggle $component, ?Model $record) => $component->state($record !== null && $record->published_at === null));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function applica(array $data, bool $bozza, ?Model $record = null): array
    {
        $data['published_at'] = $bozza ? null : ($record?->published_at ?? now());

        return $data;
    }
}
