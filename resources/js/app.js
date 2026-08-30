// Tema

// Il tema iniziale è applicato in tema.blade.php per evitare il flash bianco.
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
        // La scelta resta valida solo per questa visita.
    }

    barraDelBrowser(scelta);
    segnaAttivo(scelta);
}

// La theme-color forzata precede quelle automatiche e viene rimossa in auto.
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

document.addEventListener('click', (evento) => {
    const pulsante = evento.target.closest('[data-tema]');

    if (pulsante) {
        applica(pulsante.dataset.tema);
    }
});

segnaAttivo(leggi());

// Slider di benvenuto

const slider = document.getElementById('benvenuto');

if (slider) {
    const pallini = document.querySelectorAll('[data-pallino]');

    const segnaIllustrazione = () => {
        const quale = Math.round(slider.scrollLeft / slider.clientWidth) + 1;

        pallini.forEach((pallino) => {
            pallino.toggleAttribute('aria-current', Number(pallino.dataset.pallino) === quale);
        });
    };

    // Non blocca lo scroll su mobile.
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

        // Dopo un'interazione, l'autoplay riparte dopo PAUSA.
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

// Visibilità password

document.querySelectorAll('[data-toggle-password]').forEach((bottone) => {
    const campo = bottone.closest('.relative')?.querySelector('input');
    const occhio = bottone.querySelector('[data-eye]');
    const occhioChiuso = bottone.querySelector('[data-eye-closed]');

    if (!campo || !occhio || !occhioChiuso) return;

    bottone.addEventListener('click', () => {
        const rivela = campo.type === 'password';
        campo.type = rivela ? 'text' : 'password';
        occhio.classList.toggle('hidden', rivela);
        occhioChiuso.classList.toggle('hidden', !rivela);
        bottone.setAttribute('aria-label', rivela ? 'Nascondi password' : 'Mostra password');
    });
});
