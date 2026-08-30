<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

class EventController extends Controller
{
    /**
     * Gli eventi del gruppo (P33): raduni, one-shot, serate speciali.
     *
     * Cosa diversa dalle serate di campagna, che appartengono a una storia.
     *
     * Si vede **solo il pubblicato**: una data futura in `published_at` è una
     * pubblicazione programmata, e mostrarla in anticipo vanificherebbe il
     * motivo per cui è stata programmata.
     */
    public function index(): View
    {
        return view('events.index', [
            'upcoming' => Event::published()->upcoming()->get(),
            'past' => Event::published()->past()->get(),
        ]);
    }

    /**
     * Il dettaglio (P34): locandina, quando, dove, la descrizione per esteso.
     *
     * Un evento non ancora pubblicato non esiste per chi guarda — 404, non
     * «non hai il permesso»: dire che c'è qualcosa di nascosto è già dire
     * qualcosa di una sorpresa che era programmata apposta.
     */
    public function show(Event $event): View
    {
        abort_unless($event->isPublished(), 404);

        return view('events.show', ['event' => $event]);
    }
}
