<?php

namespace App\Filament\Resources\Events;

use App\Enums\Icon;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Resources\Events\Schemas\EventForm;
use App\Filament\Resources\Events\Tables\EventsTable;
use App\Models\Event;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Icon::Events;

    protected static string|UnitEnum|null $navigationGroup = 'Redazione';

    protected static ?string $modelLabel = 'evento';

    protected static ?string $pluralModelLabel = 'eventi';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return EventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventsTable::configure($table);
    }

    /**
     * Il pannello è un'altra cosa dal sito pubblico: l'elenco delle news lo
     * legge tutto il gruppo (`viewAny` nella policy è aperto), ma la sezione
     * di redazione è degli admin. Filament usa la stessa policy per entrambi,
     * quindi la distinzione va fatta qui.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }
}
