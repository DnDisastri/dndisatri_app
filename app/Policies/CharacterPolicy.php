<?php

namespace App\Policies;

use App\Models\Character;
use App\Models\User;

/**
 * I permessi dipendono dal ruolo, non dalla campagna (D1): ogni DM agisce su
 * ogni personaggio. Gli admin gestiscono tutto ma non hanno personaggi.
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
     * La scheda per intero (P14): caratteristiche, abilità, zaino, monete, note,
     * storia privata. Al proprietario, ai DM e agli admin; gli altri vedono solo
     * la parte pubblica.
     */
    public function viewFullSheet(User $user, Character $character): bool
    {
        return $character->user_id === $user->getKey()
            || $user->isDm()
            || $user->isAdmin();
    }

    /**
     * Il registro del personaggio (P11), con monete e compravendite: non è
     * pubblico come il Libro Mastro. Proprietario, DM e admin.
     */
    public function viewLedger(User $user, Character $character): bool
    {
        return $character->user_id === $user->getKey()
            || $user->isDm()
            || $user->isAdmin();
    }

    /**
     * Un giocatore ha un solo personaggio vivo alla volta; i DM più d'uno;
     * gli admin nessuno.
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

    /** Slot e riposi sono stato della sessione, non modifiche: niente approvazione. */
    public function manageSlots(User $user, Character $character): bool
    {
        return $this->playsOrRuns($user, $character);
    }

    /**
     * Indossare e riporre: mossa di gioco, niente da approvare (la CA si ricalcola).
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
     * Vetrina degli scambi e note sugli oggetti: solo il proprietario, il DM no.
     */
    public function manageTradeable(User $user, Character $character): bool
    {
        return $this->ownsAndAlive($user, $character);
    }

    /** Il proprietario, o un DM per rimediare; su un caduto nessuno. */
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
