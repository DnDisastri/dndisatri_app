<?php

namespace App\Policies;

use App\Models\Character;
use App\Models\User;

/**
 * I permessi dipendono dal ruolo, non dal tavolo (decisione D1 in
 * docs/PIANO.md): ogni DM agisce su ogni personaggio, perché prima o poi ogni
 * giocatore può finire al tavolo di ogni DM.
 *
 * Gli admin sono account di sola amministrazione: gestiscono tutto ma **non
 * hanno personaggi** e non giocano.
 */
class CharacterPolicy
{
    /** La Gilda è visibile a tutto il gruppo. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Character $character): bool
    {
        return true;
    }

    /**
     * La scheda **per intero** (P14).
     *
     * Aprire la scheda di un compagno lo possono tutti — `view` dice di sì a
     * chiunque — ma quello che ci si legge dentro non è lo stesso. Di un altro
     * si vede chi è e cosa sa fare: nome, storia, specie, classi, livello,
     * Classe Armatura, punti ferita, armi, incantesimi, talenti. Non si vedono
     * le sei caratteristiche, i tiri salvezza, le abilità, lo zaino, l'oro e le
     * note. **Le statistiche di un personaggio sono affare di chi lo gioca.**
     *
     * Non è pudore: è che sapere il Carisma altrui non serve a giocare
     * insieme, e sapere cosa tiene in tasca un compagno toglie il gusto di
     * chiederglielo al tavolo.
     *
     * Chi conduce le vede tutte, perché al tavolo gli servono per davvero: un
     * giocatore assente, una prova da tirare al posto suo, una scheda da
     * rimettere a posto.
     */
    public function viewFullSheet(User $user, Character $character): bool
    {
        return $character->user_id === $user->getKey()
            || $user->isDm()
            || $user->isAdmin();
    }

    /**
     * Il registro del personaggio (P11): l'estratto conto.
     *
     * **Non è pubblico come il Libro Mastro**, che è la memoria condivisa del
     * gruppo: qui c'è quanto oro ha in tasca uno alla volta, cosa ha comprato e
     * a chi l'ha venduto. Lo leggono il proprietario e chi conduce — un DM ci
     * arriva quando c'è da capire dove è finito qualcosa.
     */
    public function viewLedger(User $user, Character $character): bool
    {
        return $character->user_id === $user->getKey()
            || $user->isDm()
            || $user->isAdmin();
    }

    /**
     * Gli admin non creano personaggi: i loro sono account di amministrazione.
     *
     * Un giocatore ne ha uno solo vivo alla volta. I DM sono esenti dal
     * limite: giocano anche loro e possono averne più d'uno.
     */
    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return false;
        }

        if ($user->isDm()) {
            return true;
        }

        return ! $user->characters()->alive()->exists();
    }

    /**
     * DM e admin modificano direttamente. Il giocatore no: propone, e la
     * modifica passa dalla bacheca (vedi `propose`).
     */
    public function update(User $user, Character $character): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    /** Il proprietario propone modifiche al proprio personaggio, se è vivo. */
    public function propose(User $user, Character $character): bool
    {
        return $this->ownsAndAlive($user, $character);
    }

    /**
     * Slot incantesimo e riposi: li gestisce il proprietario senza chiedere
     * niente a nessuno, perché sono lo stato di una serata e non una modifica
     * alla scheda. Far approvare ogni Dardo Incantato sarebbe insostenibile.
     */
    public function manageSlots(User $user, Character $character): bool
    {
        return $this->playsOrRuns($user, $character);
    }

    /**
     * Indossare e riporre l'equipaggiamento, per la stessa ragione: cambiare
     * armatura in mezzo a un'avventura è una mossa di gioco.
     *
     * Cambia la Classe Armatura, ma la Classe Armatura non è un valore salvato:
     * si ricalcola da quello che il personaggio indossa, quindi non c'è niente
     * da approvare.
     */
    public function manageEquipment(User $user, Character $character): bool
    {
        return $this->playsOrRuns($user, $character);
    }

    /**
     * Preparare gli incantesimi (D16): è quello che si fa al mattino nel
     * gioco, e cambia ogni giorno per definizione.
     */
    public function managePreparedSpells(User $user, Character $character): bool
    {
        return $this->playsOrRuns($user, $character);
    }

    /**
     * La sintonia con gli oggetti magici: stessa famiglia dell'equipaggiamento.
     * Decidere cosa portare addosso in un dato momento è una mossa di gioco.
     */
    public function manageAttunement(User $user, Character $character): bool
    {
        return $this->playsOrRuns($user, $character);
    }

    /**
     * Danni e cure, per la stessa ragione ancora (decisione D7): far approvare
     * ogni colpo preso sarebbe insostenibile quanto far approvare ogni
     * incantesimo lanciato.
     */
    public function manageHitPoints(User $user, Character $character): bool
    {
        return $this->playsOrRuns($user, $character);
    }

    /**
     * Mettere un oggetto in vetrina per gli scambi.
     *
     * **Solo il proprietario**, e qui il DM non entra: gli altri comandi
     * dell'inventario sono mosse di gioco che qualcuno deve poter fare al posto
     * di un giocatore assente, questo invece è dire agli altri «questo lo
     * darei». È una volontà, e non si esprime per conto terzi.
     */
    public function manageTradeable(User $user, Character $character): bool
    {
        return $this->ownsAndAlive($user, $character);
    }

    /**
     * Chi tiene la scheda in mano durante una serata: il proprietario, e chi
     * conduce.
     *
     * I DM ci arrivano **per necessità**, non per abitudine: giocatore assente,
     * qualcosa segnato storto, una serata da chiudere. Non è il modo normale di
     * usare questi comandi — quello resta il giocatore — ma qualcuno deve poter
     * rimettere le cose a posto senza aprire il database.
     *
     * Su un personaggio caduto non ci mette mano nessuno: la scheda di un morto
     * è chiusa.
     */
    private function playsOrRuns(User $user, Character $character): bool
    {
        if (! $character->isAlive()) {
            return false;
        }

        return $character->user_id === $user->getKey()
            || $user->isDm()
            || $user->isAdmin();
    }

    /**
     * Proporre resta cosa del solo proprietario, e non è un'incoerenza: un DM
     * non ha bisogno di proporre niente, modifica direttamente.
     */
    private function ownsAndAlive(User $user, Character $character): bool
    {
        return $character->user_id === $user->getKey() && $character->isAlive();
    }

    /**
     * Strumenti diretti: oro, livelli, talenti, oggetti magici. Non passano da
     * una richiesta, ma finiscono tutti nel Registro.
     */
    public function grant(User $user, Character $character): bool
    {
        return $user->isDm() || $user->isAdmin();
    }

    /** La morte è irreversibile. */
    public function kill(User $user, Character $character): bool
    {
        return ($user->isDm() || $user->isAdmin()) && $character->isAlive();
    }

    /** Cancellare un personaggio non è un'azione di gioco. */
    public function delete(User $user, Character $character): bool
    {
        return $user->isAdmin();
    }
}
