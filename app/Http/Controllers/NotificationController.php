<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Le notifiche ricevute.
 *
 * Aprire la pagina le segna come lette: il pallino serve a dire «c'è qualcosa
 * di nuovo», e una volta guardato il suo lavoro è finito. Un pulsante «segna
 * come letto» sarebbe un clic in più per ottenere la stessa cosa.
 *
 * Le notifiche non si cancellano: chi rientra dopo una settimana deve poter
 * ricostruire cosa si è perso. Ma non restano nemmeno in lista all'infinito —
 * si **archiviano**, cioè si tolgono di mezzo tenendole nel database, e si
 * rivedono con «mostra archiviate».
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $mostraArchiviate = $request->boolean('archiviate');

        // Si legge la lista prima di segnarla letta, o la pagina mostrerebbe
        // tutto già vecchio al primo colpo d'occhio.
        $notifications = $user->notifications()
            ->when($mostraArchiviate,
                fn ($q) => $q->whereNotNull('archived_at'),
                fn ($q) => $q->whereNull('archived_at'))
            ->latest()
            ->limit(50)
            ->get();

        // Solo le attive si segnano lette: l'archivio è già roba vista.
        if (! $mostraArchiviate) {
            $user->unreadNotifications()->whereNull('archived_at')->update(['read_at' => now()]);
        }

        return view('notifications.index', [
            'notifications' => $notifications,
            'mostraArchiviate' => $mostraArchiviate,
            'archiviate' => $user->notifications()->whereNotNull('archived_at')->count(),
            'daSvuotare' => $user->notifications()->whereNull('archived_at')->count(),
        ]);
    }

    /** Mettere via una notifica: sparisce dalla lista, resta nel database. */
    public function archive(Request $request, string $notification): RedirectResponse
    {
        // `findOrFail` sulla relazione: una notifica che non è tua non la si
        // trova nemmeno, e chiedere di archiviarla dà 404.
        $request->user()->notifications()->findOrFail($notification)
            ->update(['archived_at' => now()]);

        return back()->with('status', 'Notifica archiviata.');
    }

    /** Svuota: archivia tutte le notifiche attive in un colpo solo. */
    public function clear(Request $request): RedirectResponse
    {
        $request->user()->notifications()
            ->whereNull('archived_at')
            ->update(['archived_at' => now()]);

        return back()->with('status', 'Notifiche archiviate.');
    }

    /** Ripescarla dall'archivio: torna in lista. */
    public function restore(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($notification)
            ->update(['archived_at' => null]);

        return back()->with('status', 'Notifica ripristinata.');
    }
}
