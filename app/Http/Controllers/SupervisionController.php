<?php

namespace App\Http\Controllers;

use App\Models\SupervisedAction;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * P29 — le mie azioni in attesa di via libera.
 *
 * L'altra faccia di M24/M25: là un DM approva o blocca, qui il giocatore
 * **sotto richiamo** vede cosa ha chiesto e come è finita. Quando è stata
 * chiesta, l'applicazione risponde «è in attesa che un DM la approvi», e questa
 * è la pagina dove quell'attesa ha un posto invece di sparire in un messaggio
 * che scorre via.
 *
 * Non si fa niente da qui: la decisione è del DM, e il giocatore non ritira né
 * riprova — se una vendita viene bloccata, la ripropone dal mercato come una
 * qualunque. È una pagina da leggere, e per questo è un controller e non un
 * componente Livewire.
 */
class SupervisionController extends Controller
{
    public function mine(Request $request): View
    {
        $user = $request->user();

        /*
         * La pagina esiste per la vigilanza, e a chi non c'entra non si apre.
         * Non basta essere sotto richiamo adesso: chi lo è stato ha azioni già
         * decise da rileggere — è lì che sta scritto perché una fu bloccata —
         * e togliere il richiamo non deve portarsi via quella spiegazione.
         */
        $historyExists = SupervisedAction::where('user_id', $user->getKey())->exists();

        abort_unless($user->isUnderWarning() || $historyExists, 404);

        $mie = SupervisedAction::where('user_id', $user->getKey())
            ->with('reviewedBy')
            ->latest('id')
            ->get();

        return view('market.vigilanza', [
            // In attesa in cima: sono le uniche che non hanno ancora una
            // risposta, e sono il motivo per cui si apre la pagina. Le decise
            // stanno sotto, dalla più recente, per rileggere un esito.
            'inAttesa' => $mie->filter->isPending()->values(),
            'decise' => $mie->reject->isPending()->values(),
            'sottoRichiamo' => $user->isUnderWarning(),
        ]);
    }
}
