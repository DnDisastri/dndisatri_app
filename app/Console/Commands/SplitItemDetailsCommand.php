<?php

namespace App\Console\Commands;

use App\Models\CharacterItem;
use Illuminate\Console\Command;

/**
 * Prima che il modulo del bottino avesse i dettagli a parte, la descrizione finiva
 * nel nome («Adamantine Armor - Nonostante sia nuova…»). Qui si separa: nome breve,
 * descrizione in testa ai dettagli. Tipo e bonus restano al DM, dallo zaino.
 */
class SplitItemDetailsCommand extends Command
{
    protected $signature = 'dndisastri:separa-dettagli
        {--id=* : Solo questi oggetti (character_items.id)}
        {--dry-run : Mostra cosa farebbe senza salvare}';

    protected $description = 'Sposta nei dettagli la descrizione scritta nel nome degli oggetti';

    private const SEPARATORE = '/\s+[-–]\s+/u';

    public function handle(): int
    {
        $prova = (bool) $this->option('dry-run');
        $righe = [];

        CharacterItem::query()
            ->with('character:id,name')
            ->when($this->option('id'), fn ($query, array $ids) => $query->whereKey($ids))
            ->where(fn ($query) => $query->where('name', 'like', '% - %')->orWhere('name', 'like', '% – %'))
            ->orderBy('id')
            ->each(function (CharacterItem $item) use ($prova, &$righe) {
                [$nome, $descrizione] = array_map('trim', preg_split(self::SEPARATORE, $item->name, 2));

                if ($nome === '' || $descrizione === '') {
                    return;
                }

                $dettagli = filled($item->details) ? "{$descrizione}\n\n{$item->details}" : $descrizione;

                $righe[] = [$item->id, $item->character?->name ?? "#{$item->character_id}", $nome, mb_strimwidth($descrizione, 0, 60, '…')];

                if (! $prova) {
                    $item->forceFill(['name' => $nome, 'details' => mb_substr($dettagli, 0, 1000)])->save();
                }
            });

        if ($righe === []) {
            $this->info('Nessun oggetto da sistemare.');

            return self::SUCCESS;
        }

        $this->table(['Oggetto', 'Personaggio', 'Nome nuovo', 'Nei dettagli'], $righe);
        $this->info($prova ? 'Prova: niente è stato salvato.' : 'Fatto. Tipo e bonus si assegnano dallo zaino, con «Modifica».');

        return self::SUCCESS;
    }
}
