<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use App\Models\Campaign;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('La campagna')
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
                                ->label('Prima stagione')
                                ->numeric()
                                ->minValue(1)
                                ->required()
                                // Default: la stagione più alta già esistente.
                                ->default(fn () => max(Campaign::seasons() ?: [1]))
                                ->helperText('Serve a raggruppare le campagne nell\'elenco.')
                                ->hidden(fn (Get $get) => (bool) $get('is_special')),

                            TextInput::make('last_season')
                                ->label('Ultima stagione')
                                ->numeric()
                                ->minValue(1)
                                ->gte('season')
                                ->helperText('Solo se dura più stagioni. Vuota = una stagione sola.')
                                ->hidden(fn (Get $get) => (bool) $get('is_special')),

                            TextInput::make('price_modifier')
                                ->label('Prezzi del Manuale')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->minValue(Campaign::PRICE_MODIFIER_MIN)
                                ->maxValue(Campaign::PRICE_MODIFIER_MAX)
                                ->suffix('%')
                                ->helperText('Rincara o sconta il listino del Manuale per questa campagna, da -50 a +100. L\'Emporio non cambia.'),
                        ]),

                    FileUpload::make('cover_path')
                        ->label('Copertina')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->disk('public')
                        ->directory('campagne')
                        ->maxSize(4096)
                        ->helperText('È tutto quello che si vede nell\'elenco delle campagne, insieme al titolo.'),

                    FileUpload::make('background_path')
                        ->label('Sfondo della pagina')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
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
                    // Solo un admin la crea: la speciale è di tutti i DM e non si chiude.
                    Toggle::make('is_special')
                        ->label('Campagna delle sessioni speciali')
                        ->helperText('Raccoglie multitavolo e altre sessioni fuori dalle campagne: niente DM titolare, la gestiscono tutti i DM, non si chiude.')
                        ->live()
                        ->visible(fn () => auth()->user()->isAdmin())
                        ->columnSpanFull(),

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
                        ->required(fn (Get $get) => ! $get('is_special'))
                        ->hidden(fn (Get $get) => (bool) $get('is_special'))
                        // Solo un admin può assegnare la campagna a un altro DM.
                        ->default(fn () => auth()->id())
                        ->disabled(fn () => ! auth()->user()->isAdmin())
                        ->dehydrated(),

                ])
                ->columns(2),

            Section::make('Il capogilda')
                ->description('Il personaggio che affida le quest, e che di fatto fa succedere le sessioni. Vale per tutte le quest della campagna.')
                ->schema([
                    TextInput::make('quest_giver')
                        ->label('Nome')
                        ->maxLength(255),

                    FileUpload::make('quest_giver_photo')
                        ->label('Ritratto')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
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
