<?php

namespace App\Providers;

use App\Domain\Dnd\SubclassCatalogue;
use App\Http\Controllers\GuestBookingController;
use App\Notifications\InAppNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Va qui e non in bootstrap/app.php: lì il .env non è ancora caricato.
        if ($publicPath = config('app.public_path')) {
            $this->app->usePublicPath($publicPath);
        }

        // Singleton perché facciano da memoria per la durata della richiesta.
        $this->app->singleton(SubclassCatalogue::class);
        $this->app->singleton(SubraceCatalogue::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Niente Gate::before per gli admin: "l'admin può tutto" ha già due
        // eccezioni (non ha personaggi, non approva le proprie richieste) e
        // una scorciatoia globale le nasconderebbe. Ogni policy dice
        // esplicitamente cosa vale per chi.

        // In sviluppo: rompe subito se una relazione viene usata senza
        // caricarla, invece di lasciar passare le query N+1 (§8.6 del brief).
        Model::preventLazyLoading(! app()->isProduction());

        /*
         * La regola delle password, in un posto solo: almeno 8 caratteri, e
         * **in produzione** anche il controllo contro le liste di quelle bucate
         * (Have I Been Pwned, in k-anonymity: non parte mai la password intera).
         *
         * Il controllo sta solo in produzione di proposito: in test e in
         * sviluppo eviterebbe di usare «password», farebbe una chiamata di rete
         * a ogni prova, e non aggiunge sicurezza dove non c'è nessuno vero.
         */
        Password::defaults(function () {
            $regola = Password::min(8);

            return app()->isProduction() ? $regola->uncompromised() : $regola;
        });

        /*
         * Il tetto dell'hosting è 250 email l'ora su tutto l'account. Qui si
         * sta sotto di cinquanta, che restano per i recuperi password e per
         * quello che non passa di qui.
         */
        RateLimiter::for(InAppNotification::LIMITATORE, fn () => Limit::perHour(200));

        // Il modulo pubblico degli ospiti: ogni invio manda un'email, quindi pochi per IP e per indirizzo.
        RateLimiter::for(GuestBookingController::LIMITATORE, fn (Request $request) => [
            Limit::perHour(5)->by('ip:'.$request->ip()),
            Limit::perHour(3)->by('email:'.(string) $request->input('email')),
        ]);
    }
}
