<?php

namespace App\Filament\Resources\Subclasses\Schemas;

use App\Domain\Dnd\ClassRules;
use App\Domain\Dnd\Progression;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SubclassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Select::make('class')
                        ->label('Classe')
                        ->required()
                        // Le dodici del manuale: le classi non si aggiungono
                        // dal pannello, solo le loro specializzazioni.
                        ->options(fn () => ClassRules::names()->mapWithKeys(fn (string $c) => [$c => $c])->all())
                        ->searchable()
                        ->live()
                        ->helperText(fn (Get $get) => filled($get('class'))
                            ? 'Un '.$get('class').' la sceglie al livello '.Progression::subclassLevel($get('class')).' della classe.'
                            : null),

                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label('In una riga')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('Serve a orientare chi sceglie, non a spiegare le regole.')
                        ->columnSpanFull(),

                    Toggle::make('third_caster')
                        ->label('Lancia incantesimi')
                        ->helperText('Accendilo solo se la classe di per sé non lancia, come il Cavaliere Mistico.'),

                    Toggle::make('is_homebrew')
                        ->label('Fatta in casa')
                        ->default(true)
                        ->helperText('Spenta = viene dal manuale. Quelle da manuale non si cancellano.'),

                    TextInput::make('position')
                        ->label('Posizione')
                        ->numeric()
                        ->default(99)
                        ->helperText('Ordine nell\'elenco a tendina. A parità conta l\'ordine di inserimento.'),
                ])
                ->columns(2),
        ])->columns(1);
    }
}
