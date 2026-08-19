<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
    }
}
