<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

class EventController extends Controller
{
    /** Gli eventi del gruppo (P33), fuori dalle campagne; solo quelli già pubblicati. */
    public function index(): View
    {
        return view('events.index', [
            'upcoming' => Event::published()->upcoming()->get(),
            'past' => Event::published()->past()->get(),
        ]);
    }

    /** Il dettaglio (P34). Non ancora pubblicato: 404, per non svelare la sorpresa. */
    public function show(Event $event): View
    {
        abort_unless($event->isPublished(), 404);

        return view('events.show', ['event' => $event]);
    }
}
