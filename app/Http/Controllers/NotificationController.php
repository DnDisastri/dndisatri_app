<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Aprire la pagina segna lette le notifiche attive. Si archiviano invece di
 * cancellarle; si eliminano per sempre solo dall'archivio.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $mostraArchiviate = $request->boolean('archiviate');

        // Letta prima di segnarla, o la pagina mostrerebbe tutto già vecchio.
        $notifications = $user->notifications()
            ->when($mostraArchiviate,
                fn ($q) => $q->whereNotNull('archived_at'),
                fn ($q) => $q->whereNull('archived_at'))
            ->latest()
            ->limit(50)
            ->get();

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

    public function archive(Request $request, string $notification): RedirectResponse
    {
        // Cercata fra le proprie: quella di un altro dà 404.
        $request->user()->notifications()->findOrFail($notification)
            ->update(['archived_at' => now()]);

        return back()->with('status', 'Notifica archiviata.');
    }

    /** Archivia tutte le notifiche attive. */
    public function clear(Request $request): RedirectResponse
    {
        $request->user()->notifications()
            ->whereNull('archived_at')
            ->update(['archived_at' => now()]);

        return back()->with('status', 'Notifiche archiviate.');
    }

    public function restore(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($notification)
            ->update(['archived_at' => null]);

        return back()->with('status', 'Notifica ripristinata.');
    }

    /** Solo dall'archivio: una notifica attiva non si elimina senza averla messa via. */
    public function destroy(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->whereNotNull('archived_at')->findOrFail($notification)->delete();

        return back()->with('status', 'Notifica eliminata.');
    }

    public function emptyArchive(Request $request): RedirectResponse
    {
        $request->user()->notifications()->whereNotNull('archived_at')->delete();

        return redirect()->route('notifications.index')->with('status', 'Archivio svuotato.');
    }
}
