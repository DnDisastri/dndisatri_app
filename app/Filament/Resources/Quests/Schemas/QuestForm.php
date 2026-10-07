<?php

namespace App\Filament\Resources\Quests\Schemas;

use App\Domain\Dnd\Coin;
use App\Domain\Dnd\Coins;
use App\Enums\QuestDifficulty;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
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
                        ->label('Link')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Si scrive da solo dal titolo. Modificalo solo se vuoi: compare nel link della quest.'),

                    Textarea::make('description')
                        ->label('Descrizione')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),

                    Textarea::make('setting')
                        ->label('Ambientazione')
                        ->rows(2)
                        ->columnSpanFull(),

                    // Una quest deve dare una ricompensa: basta una delle tre parti.
                    Grid::make(4)
                        ->schema(collect(Coin::descending())->map(fn (Coin $moneta) => TextInput::make("reward_coins.{$moneta->value}")
                            ->label($moneta->label())
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(Coins::MAX)
                            ->suffix($moneta->abbreviation())
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? (int) $state : null)
                            ->when($moneta === Coin::Platinum, fn (TextInput $campo) => $campo
                                ->requiredWithoutAll('reward_coins.gp,reward_coins.sp,reward_coins.cp,reward_items,rewards')
                                ->validationMessages([
                                    'required_without_all' => 'Metti almeno una ricompensa: monete, oggetti o testo.',
                                ])))
                            ->all())
                        ->columnSpanFull(),

                    TagsInput::make('reward_items')
                        ->label('Oggetti magici')
                        ->placeholder('Aggiungi un oggetto')
                        ->helperText('Uno per invio. Anche solo «2 oggetti magici da trovare».')
                        ->columnSpanFull(),

                    Textarea::make('rewards')
                        ->label('Altre ricompense')
                        ->helperText('Testo libero: un favore, un titolo, un indizio… ciò che non è monete né oggetti.')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            // `completed_at`, `closed_at` e la sessione passano dalle azioni di dominio: la sessione si sceglie dalla pagina della quest, che avvisa gli interessati.
        ])->columns(1);
    }
}
