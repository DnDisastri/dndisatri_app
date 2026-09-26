import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            /*
             * I caratteri si scaricano in fase di build e vengono serviti dal
             * nostro dominio: nessuna chiamata a Google mentre qualcuno usa
             * l'applicazione, e le pagine non aspettano un terzo.
             *
             * Bowlby One esiste in un peso solo — è un carattere da titoli e
             * non ne ha bisogno di altri.
             */
            fonts: [
                bunny('Bowlby One', {
                    weights: [400],
                }),
                bunny('Quicksand', {
                    weights: [400, 500, 600, 700],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
