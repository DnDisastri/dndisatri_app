<?php

namespace App\Filament\Resources\AboutPages\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AboutPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Textarea::make('body')
                        ->label('Testo')
                        ->required()
                        ->rows(12)
                        ->helperText('Chi siete, cosa fate. Gli a capo si vedono anche nella pagina.')
                        ->columnSpanFull(),

                    FileUpload::make('cover_path')
                        ->label('Copertina')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/jpeg'])
                        ->disk('public')
                        ->directory('chi-siamo')
                        ->maxSize(4096)
                        ->columnSpanFull(),
                ]),

            // I tre social sono fissi: si aggiunge solo il link. Vuoto = non
            // compare. Le chiavi (`instagram`, ecc.) le legge la vista.
            Section::make('Social')
                ->description('Lascia vuoto quello che non usate: comparirà solo chi ha un link.')
                ->columns(1)
                ->schema([
                    TextInput::make('socials.instagram')
                        ->label('Instagram')
                        ->url()
                        ->maxLength(255)
                        ->placeholder('https://instagram.com/...'),

                    TextInput::make('socials.tiktok')
                        ->label('TikTok')
                        ->url()
                        ->maxLength(255)
                        ->placeholder('https://tiktok.com/@...'),

                    TextInput::make('socials.telegram')
                        ->label('Telegram')
                        ->url()
                        ->maxLength(255)
                        ->placeholder('https://t.me/...'),
                ]),
        ])->columns(1);
    }
}
