<?php

namespace App\Filament\Resources\Quests\Schemas;

use App\Enums\QuestDifficulty;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class QuestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('La quest')
                ->schema([
                    Select::make('campaign_id')
                        ->label('Campagna')
                        ->relationship('campaign', 'title')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('difficulty')
                        ->label('Difficoltà')
                        ->options(QuestDifficulty::class)
                        ->required(),

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

                    Textarea::make('description')
                        ->label('Descrizione')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),

                    Textarea::make('setting')
                        ->label('Ambientazione')
                        ->rows(2)
                        ->columnSpanFull(),

                    // La ricompensa, strutturata. Una quest **deve** darne una:
                    // basta una delle tre parti, e `required_without_all` sul
                    // primo campo lo garantisce senza obbligare a riempirle tutte.
                    TextInput::make('reward_gold')
                        ->label('Oro')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('mo')
                        ->requiredWithoutAll('reward_items,rewards')
                        ->validationMessages([
                            'required_without_all' => 'Metti almeno una ricompensa: oro, oggetti o testo.',
                        ]),

                    TagsInput::make('reward_items')
                        ->label('Oggetti magici')
                        ->placeholder('Aggiungi un oggetto')
                        ->helperText('Uno per invio. Anche solo «2 oggetti magici da trovare».')
                        ->columnSpanFull(),

                    Textarea::make('rewards')
                        ->label('Altre ricompense')
                        ->helperText('Testo libero: un favore, un titolo, un indizio… ciò che non è oro né oggetti.')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Quanti giocatori')
                ->description('Il minimo dice se la serata sta in piedi; non impedisce niente, la decisione resta tua.')
                ->schema([
                    TextInput::make('min_participants')
                        ->label('Minimo')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->default(3)
                        ->helperText('Sotto questo numero il tavolo probabilmente non vale la serata.'),

                    TextInput::make('max_participants')
                        ->label('Massimo')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->default(5)
                        ->helperText('I posti veri. Chi arriva dopo entra in lista d\'attesa.'),
                ])
                ->columns(2),

            // `completed_at` e `closed_at` non stanno qui: concludere una quest
            // è irreversibile e passa dall'azione di dominio, che è la garanzia
            // che nessuno lo faccia per sbaglio da un modulo. Lo stesso vale
            // per `night_confirmed_at`, che si scrive con «la serata si fa».
        ]);
    }
}
