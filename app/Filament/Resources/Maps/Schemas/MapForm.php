<?php

namespace App\Filament\Resources\Maps\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MapForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('campaign_id')
                    ->relationship('campaign', 'title'),
                TextInput::make('uploaded_by')
                    ->numeric(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                FileUpload::make('image_path')
                    ->image()
                    ->required(),
            ]);
    }
}
