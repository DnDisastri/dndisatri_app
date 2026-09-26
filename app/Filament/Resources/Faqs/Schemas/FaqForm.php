<?php

namespace App\Filament\Resources\Faqs\Schemas;

use App\Models\Faq;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqForm
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
                        ->datalist(fn () => Faq::query()
                            ->whereNotNull('category')
                            ->distinct()
                            ->orderBy('category')
                            ->pluck('category')
                            ->all())
                        ->helperText('Raggruppa le voci nella guida. Vuoto = voce senza sezione.'),

                    TextInput::make('question')
                        ->label('Domanda')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Textarea::make('answer')
                        ->label('Risposta')
                        ->required()
                        ->rows(10)
                        ->helperText('Gli a capo si vedono anche nella guida.')
                        ->columnSpanFull(),

                    Toggle::make('is_published')
                        ->label('Pubblicata')
                        ->default(true)
                        ->helperText('Spenta = bozza, non visibile nella guida.'),
                ])
                ->columns(2),
        ])->columns(1);
    }
}
