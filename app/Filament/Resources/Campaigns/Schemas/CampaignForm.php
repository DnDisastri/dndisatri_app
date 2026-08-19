<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use App\Models\Campaign;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                        ->label('Indirizzo')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    TextInput::make('season')
                        ->label('Season')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        // La season di solito è quella in corso: si propone
                        // la più alta che esiste già.
                        ->default(fn () => max(Campaign::seasons() ?: [1]))
                        ->helperText('Serve a raggruppare le campagne nell\'elenco.'),

                    FileUpload::make('cover_path')
                        ->label('Copertina')
                        ->image()
                        ->disk('public')
                        ->directory('campagne')
                        ->maxSize(4096)
                        ->helperText('È tutto quello che si vede nell\'elenco delle campagne, insieme al titolo.'),

                    FileUpload::make('background_path')
                        ->label('Sfondo della pagina')
                        ->image()
                        ->disk('public')
                        ->directory('campagne/sfondi')
                        ->maxSize(4096)
                        ->helperText('Sta dietro al testo della pagina della campagna, sotto un velo. Una trama o una mappa sbiadita funzionano; una foto con un soggetto forte no. Se lo lasci vuoto si usa la copertina.'),

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
                            // Solo chi ha il ruolo: un giocatore non conduce.
                            fn ($query) => $query->whereHas('roles', fn ($q) => $q->where('name', User::ROLE_DM))
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        // Un DM apre i propri tavoli e basta: solo un admin può
                        // assegnare la campagna a qualcun altro.
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
        ]);
    }
}
