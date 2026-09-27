<?php

namespace App\Filament\Resources\Subraces\Schemas;

use App\Domain\Dnd\Ability;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubraceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Select::make('race')
                        ->label('Razza')
                        ->required()
                        // Le razze del manuale: non si aggiungono dal pannello,
                        // solo le loro varianti.
                        ->options(fn () => collect(array_keys(config('dnd.species', [])))
                            ->mapWithKeys(fn (string $r) => [$r => $r])
                            ->all())
                        ->searchable(),

                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label('In una riga')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('Serve a orientare chi sceglie.')
                        ->columnSpanFull(),

                    // Solo i numeri che l'applicazione somma davvero. Tutto il
                    // resto (resistenze, competenze, incantesimi) va nei tratti.
                    KeyValue::make('asi')
                        ->label('Bonus alle caratteristiche')
                        ->keyLabel('Caratteristica')
                        ->valueLabel('Bonus')
                        ->keyPlaceholder(collect(Ability::cases())->map(fn (Ability $a) => $a->value)->join(', '))
                        ->helperText('Si sommano a quelli della razza. Vuoto per discendenze draconiche ed etnie.')
                        ->columnSpanFull(),

                    TextInput::make('speed')
                        ->label('Velocità in metri')
                        ->numeric()
                        ->step(0.5)
                        ->helperText('Solo se diversa da quella della razza, come l\'elfo dei boschi. Vuoto = quella della razza.'),

                    Toggle::make('is_homebrew')
                        ->label('Fatta in casa')
                        ->default(true)
                        ->helperText('Spenta = viene dal manuale. Quelle da manuale non si cancellano.'),

                    Textarea::make('traits')
                        ->label('Tratti')
                        ->rows(4)
                        ->helperText('Finiscono sulla scheda sotto quelli della razza: resistenze, competenze, incantesimi.')
                        ->columnSpanFull(),

                    TextInput::make('position')
                        ->label('Posizione')
                        ->numeric()
                        ->default(99)
                        ->helperText('Ordine nell\'elenco. A parità conta l\'ordine di inserimento.'),
                ])
                ->columns(2),
        ])->columns(1);
    }
}
