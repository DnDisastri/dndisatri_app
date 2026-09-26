<?php

namespace App\Filament\Resources\BugReports\Schemas;

use App\Enums\BugReportStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BugReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('La segnalazione')
                ->schema([
                    TextEntry::make('reporter.name')
                        ->label('Chi l\'ha segnalato')
                        ->placeholder('Account cancellato'),
                    TextEntry::make('reporter.email')
                        ->label('Email')
                        ->placeholder('Vuoto'),
                    TextEntry::make('created_at')->label('Ricevuta')->dateTime('d/m/Y H:i'),
                    TextEntry::make('title')->label('In una riga')->columnSpanFull(),
                    TextEntry::make('description')->label('Racconto')->columnSpanFull(),
                ])
                ->columns(3),

            // Raccolti dall'applicazione: è quello che serve per riprodurre il
            // problema e che nessuno saprebbe riferire a memoria.
            Section::make('Dove e con cosa')
                ->schema([
                    TextEntry::make('page')
                        ->label('Pagina')
                        ->placeholder('Vuoto')
                        ->url(fn ($state) => $state)
                        ->openUrlInNewTab()
                        ->columnSpanFull(),
                    TextEntry::make('user_agent')
                        ->label('Browser')
                        ->placeholder('Vuoto')
                        ->columnSpanFull(),
                ])
                ->collapsed(),

            Section::make('Com\'è finita')
                ->schema([
                    TextEntry::make('status')
                        ->label('Stato')
                        ->badge()
                        ->formatStateUsing(fn (BugReportStatus $state) => $state->label())
                        ->color(fn (BugReportStatus $state) => $state->color()),
                    TextEntry::make('closer.name')->label('Chiusa da')->placeholder('Vuoto'),
                    TextEntry::make('closed_at')->label('Quando')->dateTime('d/m/Y H:i')->placeholder('Vuoto'),
                    TextEntry::make('answer')
                        ->label('Risposta mandata a chi ha segnalato')
                        ->placeholder('Nessuna')
                        ->columnSpanFull(),
                ])
                ->columns(3),
        ]);
    }
}
