<?php

namespace App\Filament\Resources\PendingChanges\Pages;

use App\Actions\Characters\ApprovePendingChange;
use App\Actions\Characters\RejectPendingChange;
use App\Enums\EquipmentSlot;
use App\Enums\Icon;
use App\Enums\PendingChangeType;
use App\Filament\Resources\PendingChanges\PendingChangeResource;
use App\Models\CharacterItem;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use RuntimeException;

class ViewPendingChange extends ViewRecord
{
    protected static string $resource = PendingChangeResource::class;

    public function getTitle(): string
    {
        return 'Richiesta '.lcfirst($this->record->type->label());
    }

    public function getBreadcrumb(): string
    {
        return $this->record->type->label();
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->approveAction(),
            $this->rejectAction(),
        ];
    }

    private function approveAction(): Action
    {
        return Action::make('approva')
            ->label('Approva')
            ->icon(Icon::Approve)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Applicare la modifica?')
            ->modalDescription(fn () => match (true) {
                $this->record->isStale() => 'Attenzione: la scheda è cambiata dopo questa proposta.',
                $this->record->type === PendingChangeType::Barter => 'Il giocatore riceve l\'articolo e il suo oggetto entra nel magazzino dell\'Emporio.',
                default => 'La scheda verrà aggiornata e il movimento finirà nel Registro.',
            })
            ->schema(fn () => [
                ...$this->itemFields(),
                Textarea::make('note')->label('Nota (facoltativa)')->rows(2),
            ])
            ->visible(fn () => $this->record->isPending())
            ->authorize(fn () => auth()->user()->can('approve', $this->record))
            ->action(function (array $data) {
                try {
                    app(ApprovePendingChange::class)->handle(
                        $this->record,
                        auth()->user(),
                        $data['note'] ?? null,
                        $data['oggetti'] ?? [],
                    );
                } catch (RuntimeException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('Richiesta approvata.')->success()->send();

                $this->refreshFormData([]);
            });
    }

    /**
     * Per ogni oggetto del bottino il DM conferma il tipo a catalogo e il +N:
     * il giocatore li propone, ma decidono CA e attacchi.
     *
     * @return list<Grid>
     */
    private function itemFields(): array
    {
        if ($this->record->type !== PendingChangeType::Loot) {
            return [];
        }

        return collect($this->record->grant_items ?? [])
            ->map(fn (array $item, int $i) => Grid::make(['default' => 1, 'sm' => 3])->schema([
                Select::make("oggetti.{$i}.base")
                    ->label("Tipo di «{$item['name']}»")
                    ->options(EquipmentSlot::bases())
                    ->placeholder('Nessuno')
                    ->default($item['base'] ?? (EquipmentSlot::isBase($item['name']) ? $item['name'] : null))
                    ->columnSpan(['default' => 1, 'sm' => 2]),
                TextInput::make("oggetti.{$i}.magic_bonus")
                    ->label('Bonus magico')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(CharacterItem::MAX_MAGIC_BONUS)
                    ->prefix('+')
                    ->default((int) ($item['magic_bonus'] ?? 0)),
            ]))
            ->values()
            ->all();
    }

    private function rejectAction(): Action
    {
        return Action::make('rifiuta')
            ->label('Rifiuta')
            ->icon(Icon::Reject)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Rifiutare la richiesta?')
            ->modalDescription('Il personaggio non verrà toccato. Il giocatore potrà riproporla.')
            ->schema([
                Textarea::make('note')->label('Motivo (facoltativo)')->rows(2),
            ])
            ->visible(fn () => $this->record->isPending())
            ->authorize(fn () => auth()->user()->can('reject', $this->record))
            ->action(function (array $data) {
                app(RejectPendingChange::class)->handle(
                    $this->record,
                    auth()->user(),
                    $data['note'] ?? null,
                );

                Notification::make()->title('Richiesta rifiutata.')->send();

                $this->refreshFormData([]);
            });
    }
}
