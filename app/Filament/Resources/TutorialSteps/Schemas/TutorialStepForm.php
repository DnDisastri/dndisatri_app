<?php

namespace App\Filament\Resources\TutorialSteps\Schemas;

use App\Enums\TutorialIllustration;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TutorialStepForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Select::make('illustration')
                        ->label('Illustrazione')
                        ->options(TutorialIllustration::options())
                        ->required()
                        ->native(false)
                        ->helperText("Il disegno abbinato al passo. È fisso nel codice: qui scegli solo quale."),

                    TextInput::make('title')
                        ->label('Titolo')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('body')
                        ->label('Testo')
                        ->required()
                        ->rows(8)
                        ->helperText('Gli a capo si vedono anche nel tutorial. Usa **parola** per il grassetto e [icona:nome] per un\'icona (es. [icona:market]).')
                        ->columnSpanFull(),

                    Toggle::make('is_published')
                        ->label('Pubblicato')
                        ->default(true)
                        ->helperText('Spento = bozza, non visibile nel tutorial.'),
                ])
                ->columns(2),
        ])->columns(1);
    }
}
