<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Models\Event;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make("L'evento")
                ->description('Cosa succede, dove e con quale immagine.')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Titolo')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        // Slug generato dal titolo solo in creazione: così non
                        // sovrascrive eventuali modifiche manuali in seguito.
                        ->afterStateUpdated(function ($state, $set, $context) {
                            if ($context === 'create') {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),

                    TextInput::make('slug')
                        ->label('Link')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText("Si scrive da solo dal titolo. Modificalo solo se vuoi: compare nel link dell'evento."),

                    TextInput::make('location')
                        ->label('Luogo')
                        ->maxLength(255)
                        ->placeholder('Dove ci si trova'),

                    Textarea::make('description')
                        ->label('Descrizione')
                        ->rows(6)
                        ->columnSpanFull(),

                    FileUpload::make('cover_path')
                        ->label('Immagine')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/jpeg'])
                        ->disk('public')
                        ->directory('eventi')
                        ->maxSize(4096)
                        ->columnSpanFull(),
                ]),

            Section::make('Quando')
                ->description('Data dell\'evento e momento della pubblicazione.')
                ->columns(2)
                ->schema([
                    DateTimePicker::make('starts_at')
                        ->label('Inizio')
                        ->seconds(false)
                        ->required(),

                    DateTimePicker::make('ends_at')
                        ->label('Fine')
                        ->seconds(false)
                        ->helperText('Facoltativo.'),

                    DateTimePicker::make('published_at')
                        ->label('Pubblicato il')
                        ->seconds(false)
                        ->helperText('Vuoto = bozza. Una data futura lo pubblica da solo.'),

                    Toggle::make('notify_players')
                        ->label('Avvisa i giocatori')
                        ->helperText('Quando l\'evento viene pubblicato. Gli eventi già passati non avvisano comunque.')
                        // Non è una colonna: anche in modifica parte acceso.
                        ->formatStateUsing(fn () => true)
                        ->dehydrated(false)
                        ->visible(fn (?Event $record) => $record?->players_notified_at === null),
                ]),
        ])->columns(1);
    }
}
