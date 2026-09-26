<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\Quest;
use App\Models\User;

/**
 * Le quest vivono dentro una campagna, e la campagna ha un proprietario:
 * le gestisce il DM di quel tavolo, più gli admin.
 *
 * È l'unica cosa che resta legata al tavolo: sui personaggi i permessi sono
 * globali (decisione D1), ma la quest è materiale di chi conduce la sessione.
 */
class QuestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Quest $quest): bool
    {
        return true;
    }

    /**
     * La campagna è opzionale perché Filament chiede «può creare quest?» senza
     * saperne ancora una: in quel caso basta il ruolo, e il tavolo giusto lo
     * impone poi l'elenco a tendina del modulo.
     */
    public function create(User $user, ?Campaign $campaign = null): bool
    {
        if ($campaign === null) {
            return $user->isAdmin() || $user->isDm();
        }

        return $campaign->isActive() && $this->runsTheTable($user, $campaign);
    }

    public function update(User $user, Quest $quest): bool
    {
        return $quest->isActive() && $this->runsTheTable($user, $quest->campaign);
    }

    /** Completare o chiudere: irreversibile, e solo se ancora attiva. */
    public function conclude(User $user, Quest $quest): bool
    {
        return $quest->isActive() && $this->runsTheTable($user, $quest->campaign);
    }

    /**
     * Prenotarsi: sempre, finché la quest è attiva e non ci si è già messi.
     *
     * **Non c'è un tetto qui**: a posti esauriti la prenotazione diventa una
     * riga in lista d'attesa, e negarla farebbe perdere proprio l'informazione
     * che le prenotazioni servono a raccogliere — quanti volevano giocare.
     *
     * Il richiamo non c'entra: la vigilanza (D13) copre le quattro azioni di
     * mercato, dove si può fare del male a qualcuno. Sedersi a un tavolo no.
     */
    public function book(User $user, Quest $quest): bool
    {
        return $quest->isActive() && ! $quest->hasParticipant($user);
    }

    public function withdraw(User $user, Quest $quest): bool
    {
        return $quest->isActive() && $quest->hasParticipant($user);
    }

    /**
     * Dichiarare che la serata si fa: tocca a chi conduce, e solo se c'è
     * qualcuno da confermare.
     */
    public function confirmNight(User $user, Quest $quest): bool
    {
        return $quest->isActive()
            && $quest->booked()->exists()
            && $this->runsTheTable($user, $quest->campaign);
    }

    /** Pescare dalla lista d'attesa: solo con un posto libero da riempire. */
    public function promote(User $user, Quest $quest): bool
    {
        return $quest->isActive()
            && ! $quest->isFull()
            && $this->runsTheTable($user, $quest->campaign);
    }

    public function delete(User $user, Quest $quest): bool
    {
        return $user->isAdmin();
    }

    private function runsTheTable(User $user, Campaign $campaign): bool
    {
        return $user->isAdmin()
            || ($user->isDm() && $campaign->dm_id === $user->getKey());
    }
}
