<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Actions\Market\GrantCoins;
use App\Domain\Dnd\Coins;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Le monete di fine sessione, uguali per tutti i personaggi segnati presenti.
 * Tutto o niente: una borsa che rifiuta annulla anche le altre. Ogni consegna
 * resta nella sessione, così si vede se sono già state date.
 */
final class AwardSessionRewards
{
    public function __construct(private readonly GrantCoins $grant) {}

    /** @return Collection<int, Character> chi le ha ricevute */
    public function handle(GameSession $session, User $actor, Coins $perHead, string $reason): Collection
    {
        if ($perHead->isEmpty() || $perHead->hasNegative()) {
            throw new InvalidArgumentException('Indica quante monete a testa.');
        }

        $personaggi = Character::query()
            ->alive()
            ->whereIn('id', $session->attendees()->pluck('game_session_user.character_id')->filter())
            ->orderBy('name')
            ->get();

        if ($personaggi->isEmpty()) {
            throw new InvalidArgumentException('Prima segna chi c\'era, con il suo personaggio.');
        }

        $motivo = "{$reason}, {$session->displayTitle()}";

        DB::transaction(function () use ($session, $actor, $perHead, $motivo, $reason, $personaggi) {
            foreach ($personaggi as $personaggio) {
                $this->grant->give($personaggio, $perHead, $actor, $motivo);
            }

            $session->forceFill([
                'rewards' => [
                    ...($session->rewards ?? []),
                    [
                        'coins' => $perHead->nonZero(),
                        'reason' => $reason,
                        'by' => $actor->name,
                        'at' => now()->toIso8601String(),
                        'characters' => $personaggi->pluck('name')->all(),
                    ],
                ],
            ])->save();
        });

        return $personaggi;
    }
}
