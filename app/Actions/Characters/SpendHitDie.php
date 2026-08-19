<?php

declare(strict_types=1);

namespace App\Actions\Characters;

use App\Domain\Dnd\Ability;
use App\Models\Character;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Spendere un dado vita.
 *
 * Si spende in due modi diversi, e l'applicazione li tiene distinti perché al
 * tavolo sono due gesti distinti:
 *
 * - **durante un riposo breve**, tirando il dado e recuperando il risultato.
 *   Il dado lo tira il giocatore, al tavolo, col suo d8 vero: qui arriva il
 *   risultato. È una scelta e non una scorciatoia — questo gruppo gioca di
 *   persona, e un'applicazione che tira al posto tuo si prenderebbe la parte
 *   migliore. Al numero tirato si aggiunge il modificatore di Costituzione,
 *   che è l'unica aritmetica che val la pena togliere di mano.
 * - **e basta**, quando è un privilegio di classe a consumarlo. Lì il dado non
 *   cura: paga qualcos'altro, e quel qualcos'altro lo sa il giocatore. Si passa
 *   `null` e la riserva cala senza toccare i punti ferita.
 *
 * Il modificatore può essere negativo: in quel caso si recupera meno del
 * tirato, e mai meno di zero. Un dado speso per niente è previsto dal
 * regolamento, e non è un errore da impedire.
 *
 * Non si controlla che sia in corso un riposo: l'applicazione non sa cosa
 * succede al tavolo, e a saperlo sono i giocatori. Quello che si controlla è
 * che il dado ci sia.
 */
final class SpendHitDie
{
    public function handle(Character $character, ?int $rolled): Character
    {
        if ($character->hitDiceLeft() < 1) {
            throw new RuntimeException(
                'Non ti restano dadi vita: tornano col riposo lungo, metà per volta.'
            );
        }

        // Speso per un privilegio di classe: la riserva cala, i punti ferita no.
        if ($rolled === null) {
            $character->forceFill(['hit_dice_used' => (int) $character->hit_dice_used + 1])->save();

            return $character;
        }

        if ($rolled < 1) {
            throw new RuntimeException('Scrivi quanto hai fatto col dado.');
        }

        if ($rolled > $character->hit_die) {
            throw new RuntimeException(
                "Un d{$character->hit_die} non fa {$rolled}."
            );
        }

        $recuperati = max(0, $rolled + $character->effectiveScores()->modifier(Ability::Con));

        return DB::transaction(function () use ($character, $recuperati) {
            $character->forceFill(['hit_dice_used' => (int) $character->hit_dice_used + 1])->save();

            return app(AdjustHitPoints::class)->heal($character, $recuperati);
        });
    }
}
