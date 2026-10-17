<?php

namespace App\Filament\Pages;

use App\Enums\Icon;
use App\Models\AppSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use UnitEnum;

/** Le impostazioni dell'applicazione che decidono gli admin. */
class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Icon::Panel;

    protected static string|UnitEnum|null $navigationGroup = 'Gestione';

    protected static ?string $navigationLabel = 'Impostazioni';

    protected static ?string $title = 'Impostazioni';

    protected static ?string $slug = 'impostazioni';

    protected static ?int $navigationSort = 50;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function content(Schema $schema): Schema
    {
        $aperte = (bool) AppSetting::get(AppSetting::RULES_2024_OPEN, false);

        return $schema->components([
            Section::make('Regole 2024 (5.5e)')
                ->description('Chi può creare personaggi con le regole 2024. I DM le possono sempre provare.')
                ->schema([
                    Text::make($aperte
                        ? 'Aperte: anche i giocatori possono scegliere le regole 2024 quando creano un personaggio.'
                        : 'Chiuse: i giocatori creano solo con le regole 2014.'),

                    Actions::make([
                        Action::make('regole2024')
                            ->label($aperte ? 'Chiudi ai giocatori' : 'Apri ai giocatori')
                            ->color($aperte ? 'gray' : 'primary')
                            ->requiresConfirmation()
                            ->modalDescription($aperte
                                ? 'I personaggi 2024 già creati restano; i giocatori non ne potranno creare altri.'
                                : 'Da adesso i giocatori potranno scegliere le regole 2024 nella creazione.')
                            ->action(function () use ($aperte) {
                                abort_unless(static::canAccess(), 403);

                                AppSetting::put(AppSetting::RULES_2024_OPEN, ! $aperte);

                                Notification::make()
                                    ->title($aperte ? 'Regole 2024 chiuse ai giocatori.' : 'Regole 2024 aperte ai giocatori.')
                                    ->success()
                                    ->send();
                            }),
                    ])->key('regole2024'),
                ]),
        ]);
    }
}
