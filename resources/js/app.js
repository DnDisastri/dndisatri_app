/*
 * La scelta del tema.
 *
 * Applicarla al primo caricamento **non** è compito di questo file: gira con
 * `defer`, quindi dopo il primo disegno, e chi ha scelto il tema scuro
 * vedrebbe un lampo bianco a ogni pagina. Quel pezzo sta in linea
 * nell'intestazione (`partials/tema.blade.php`). Qui c'è solo quello che
 * succede quando si clicca, che per definizione è dopo il caricamento.
 *
 * Tre stati e non due. «Automatico» non è un ripiego per chi non ha scelto: è
 * la scelta giusta per quasi tutti, perché segue il telefono che di sera passa
 * a scuro da solo. Chiaro e scuro servono a chi quel comportamento non lo
 * vuole, ed è una minoranza — per questo l'automatico è il valore di partenza.
 */
const CHIAVE = 'dndisastri:tema';

function leggi() {
    try {
        const scelta = localStorage.getItem(CHIAVE);

        return scelta === 'dark' || scelta === 'light' ? scelta : 'auto';
    } catch (e) {
        return 'auto';
    }
}

function applica(scelta) {
    if (scelta === 'auto') {
        delete document.documentElement.dataset.theme;
    } else {
        document.documentElement.dataset.theme = scelta;
    }

    try {
        scelta === 'auto' ? localStorage.removeItem(CHIAVE) : localStorage.setItem(CHIAVE, scelta);
    } catch (e) {
        // Niente da salvare: la scelta vale per questa visita e basta.
    }

    barraDelBrowser(scelta);
    segnaAttivo(scelta);
}

/*
 * La striscia colorata attorno alla pagina sul telefono.
 *
 * L'intestazione ne dichiara due, una per `prefers-color-scheme`, e vanno
 * benissimo finché il tema è automatico. Quando invece si forza il contrario
 * del sistema restano indietro, e si vede: pagina scura e barra del browser
 * bianca. Il browser usa **la prima** che combacia, quindi la nostra va messa
 * in cima; tolto il vincolo, le due originali tornano a comandare.
 */
function barraDelBrowser(scelta) {
    document.querySelector('meta[name="theme-color"][data-forzata]')?.remove();

    if (scelta === 'auto') {
        return;
    }

    const meta = document.createElement('meta');
    meta.name = 'theme-color';
    meta.dataset.forzata = '';
    meta.content = scelta === 'dark' ? '#111111' : '#f5f5f5';
    document.head.prepend(meta);
}

function segnaAttivo(scelta) {
    document.querySelectorAll('[data-tema]').forEach((pulsante) => {
        pulsante.setAttribute('aria-pressed', String(pulsante.dataset.tema === scelta));
    });
}

/*
 * Delega invece di un ascoltatore per pulsante: i comandi vivono dentro un
 * `details` che il browser non stampa finché è chiuso, e agganciarli al
 * caricamento significherebbe non trovarli.
 */
document.addEventListener('click', (evento) => {
    const pulsante = evento.target.closest('[data-tema]');

    if (pulsante) {
        applica(pulsante.dataset.tema);
    }
});

segnaAttivo(leggi());

const slider = document.getElementById('benvenuto');

if (slider) {
    const pallini = document.querySelectorAll('[data-pallino]');

    const segnaIllustrazione = () => {
        const quale = Math.round(slider.scrollLeft / slider.clientWidth) + 1;

        pallini.forEach((pallino) => {
            pallino.toggleAttribute('aria-current', Number(pallino.dataset.pallino) === quale);
        });
    };

    // passive: non blocchiamo lo scroll, così su mobile resta fluido.
    slider.addEventListener('scroll', segnaIllustrazione, { passive: true });
    segnaIllustrazione();

    const quante = slider.children.length;
    const menoMovimento = window.matchMedia('(prefers-reduced-motion: reduce)');

    if (quante > 1 && !menoMovimento.matches) {
        const INTERVALLO = 5000;
        const PAUSA = 8000;

        let giro = null;
        let ripresa = null;

        const avanza = () => {
            const corrente = Math.round(slider.scrollLeft / slider.clientWidth);
            const prossima = (corrente + 1) % quante;
            slider.scrollTo({ left: prossima * slider.clientWidth, behavior: 'smooth' });
        };

        const avvia = () => {
            clearInterval(giro);
            giro = setInterval(avanza, INTERVALLO);
        };

        const ferma = () => {
            clearInterval(giro);
            giro = null;
        };

        // Un'interazione ferma il giro; riparte dopo PAUSA di quiete.
        const interazione = () => {
            ferma();
            clearTimeout(ripresa);
            ripresa = setTimeout(avvia, PAUSA);
        };

        ['pointerdown', 'wheel', 'keydown'].forEach((evento) =>
            slider.addEventListener(evento, interazione, { passive: true }));

        document.addEventListener('visibilitychange', () => {
            document.hidden ? ferma() : avvia();
        });

        avvia();
    }
}
