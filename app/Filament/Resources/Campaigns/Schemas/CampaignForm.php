<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use App\Models\Campaign;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Il tavolo')
                ->schema([
                    Grid::make(3)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('title')
                                ->label('Titolo')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
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
                                ->helperText('Si scrive da solo dal titolo. Modificalo solo se vuoi: è la parte finale dell\'indirizzo della pagina.'),

                            TextInput::make('season')
                                ->label('Stagione')
                                ->numeric()
                                ->minValue(1)
                                ->required()
                                // Default: la stagione più alta già esistente.
                                ->default(fn () => max(Campaign::seasons() ?: [1]))
                                ->helperText('Serve a raggruppare le campagne nell\'elenco.'),
                        ]),

                    FileUpload::make('cover_path')
                        ->label('Copertina')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/jpeg'])
                        ->disk('public')
                        ->directory('campagne')
                        ->maxSize(4096)
                        ->helperText('È tutto quello che si vede nell\'elenco delle campagne, insieme al titolo.'),

                    FileUpload::make('background_path')
                        ->label('Sfondo della pagina')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/jpeg'])
                        ->disk('public')
                        ->directory('campagne/sfondi')
                        ->maxSize(4096)
                        ->helperText('Sta dietro al testo della pagina della campagna, sotto un overlay. Una trama o una mappa sbiadita funzionano; una foto con un soggetto forte no. Se lo lasci vuoto si usa la copertina.'),

                    Grid::make(['default' => 1, 'sm' => 4])
                        ->schema([
                            Slider::make('background_opacity')
                                ->label('Opacità dell\'overlay')
                                ->range(0, 100)
                                ->step(1)
                                ->decimalPlaces(0)
                                ->default(85)
                                ->live()
                                ->helperText('Quanto l\'overlay copre lo sfondo: più alto rende il testo più leggibile, più basso lascia vedere di più lo sfondo.')
                                ->columnSpan(['default' => 1, 'sm' => 3]),

                            TextInput::make('background_opacity')
                                ->label('%')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->step(1)
                                ->suffix('%')
                                ->default(85)
                                ->live(onBlur: true),
                        ])
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Descrizione')
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Chi conduce')
                ->schema([
                    Select::make('dm_id')
                        ->label('Dungeon master')
                        ->relationship(
                            'dm',
                            'name',
                            // Solo utenti con ruolo DM.
                            fn ($query) => $query->whereHas('roles', fn ($q) => $q->where('name', User::ROLE_DM))
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        // Solo un admin può assegnare la campagna a un altro DM.
                        ->default(fn () => auth()->id())
                        ->disabled(fn () => ! auth()->user()->isAdmin())
                        ->dehydrated(),

                ])
                ->columns(2),

            Section::make('Il capogilda')
                ->description('Il personaggio che affida le quest, e che di fatto fa succedere le serate. Vale per tutte le quest del tavolo.')
                ->schema([
                    TextInput::make('quest_giver')
                        ->label('Nome')
                        ->maxLength(255),

                    FileUpload::make('quest_giver_photo')
                        ->label('Ritratto')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/jpeg'])
                        ->disk('public')
                        ->directory('capigilda')
                        ->maxSize(4096),

                    Textarea::make('quest_giver_description')
                        ->label('Chi è')
                        ->rows(4)
                        ->columnSpanFull()
                        ->helperText('Lo leggono i giocatori dalla pagina della campagna.'),
                ])
                ->columns(2),
        ])->columns(1);
    }
}
