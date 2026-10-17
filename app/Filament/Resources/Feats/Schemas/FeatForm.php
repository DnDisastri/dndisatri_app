<?php

namespace App\Filament\Resources\Feats\Schemas;

use App\Enums\RulesEdition;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class FeatForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('rules_edition', $get('rules_edition')))
                        ->validationMessages(['unique' => "Con queste regole c'è già un talento con questo nome."]),

                    Select::make('rules_edition')
                        ->label('Regole')
                        ->options(RulesEdition::options())
                        ->default(RulesEdition::Classic->value)
                        ->required()
                        ->selectablePlaceholder(false),

                    TextInput::make('prerequisite')
                        ->label('Prerequisito')
                        ->maxLength(255)
                        ->placeholder('Forza 13 o superiore')
                        ->helperText('Vuoto se non ne ha. Lo controlla il DM che approva.')
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Cosa fa')
                        ->rows(5)
                        ->helperText('Con parole vostre: finisce sulla scheda di chi lo prende.')
                        ->columnSpanFull(),

                    Toggle::make('is_homebrew')
                        ->label('Fatto in casa')
                        ->default(true)
                        ->helperText('Spento = viene dal manuale. Quelli da manuale non si cancellano.'),
                ])
                ->columns(2),
        ])->columns(1);
    }
}
