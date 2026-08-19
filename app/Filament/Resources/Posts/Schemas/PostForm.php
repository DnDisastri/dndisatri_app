<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    TextInput::make('title')
                        ->label('Titolo')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        // Lo slug si scrive da solo, ma resta correggibile:
                        // finisce nell'indirizzo e va bene solo se leggibile.
                        ->afterStateUpdated(function ($state, $set, $context) {
                            if ($context === 'create') {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),

                    TextInput::make('slug')
                        ->label('Indirizzo')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Compare nel link della news.'),

                    Textarea::make('excerpt')
                        ->label('Sommario')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText("Le poche righe che si leggono nell'elenco.")
                        ->columnSpanFull(),

                    Textarea::make('body')
                        ->label('Testo')
                        ->required()
                        ->rows(12)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Pubblicazione')
                ->schema([
                    DateTimePicker::make('published_at')
                        ->label('Pubblicata il')
                        ->seconds(false)
                        ->helperText('Vuoto = bozza. Una data futura la pubblica da sola.'),

                    Toggle::make('is_pinned')
                        ->label('In evidenza')
                        ->helperText("Resta in cima all'elenco."),

                    FileUpload::make('cover_path')
                        ->label('Immagine')
                        ->image()
                        ->disk('public')
                        ->directory('news')
                        ->maxSize(4096)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }
}
