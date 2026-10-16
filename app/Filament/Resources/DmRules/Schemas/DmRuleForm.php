<?php

namespace App\Filament\Resources\DmRules\Schemas;

use App\Models\DmRule;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DmRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    TextInput::make('category')
                        ->label('Sezione')
                        ->maxLength(255)
                        // Suggerisce le sezioni già usate.
                        ->datalist(fn () => DmRule::query()
                            ->whereNotNull('category')
                            ->distinct()
                            ->orderBy('category')
                            ->pluck('category')
                            ->all())
                        ->helperText('Per esempio «Ricompense» o «Sessioni». Vuoto = regola senza sezione.'),

                    TextInput::make('title')
                        ->label('Regola')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Ricompensa al livello 1')
                        ->columnSpanFull(),

                    Textarea::make('body')
                        ->label('Come funziona')
                        ->required()
                        ->rows(6)
                        ->placeholder('Ai personaggi di livello 1 si danno 10 mo di ricompensa a sessione.')
                        ->helperText('Gli a capo si vedono anche nella pagina dei DM.')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ])->columns(1);
    }
}
