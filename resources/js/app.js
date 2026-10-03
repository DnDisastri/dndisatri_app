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

// Tutorial

const tutorial = document.getElementById('tutorial');

if (tutorial) {
    const slider = document.getElementById('tutorial-slider');
    const slides = Array.from(slider.children);
    const tab = Array.from(tutorial.querySelectorAll('[data-capitolo]'));
    const prima = tutorial.querySelector('[data-tutorial-prev]');
    const dopo = tutorial.querySelector('[data-tutorial-next]');

    const corrente = () => Math.round(slider.scrollLeft / slider.clientWidth);

    const vaA = (indice) => {
        const quale = Math.max(0, Math.min(slides.length - 1, indice));
        slider.scrollTo({ left: quale * slider.clientWidth, behavior: 'smooth' });
    };

    // Segna il capitolo attivo sulle tab e spegne le frecce agli estremi.
    const segna = () => {
        const i = corrente();

        tab.forEach((t) => t.toggleAttribute('aria-current', Number(t.dataset.capitolo) === i + 1));

        if (prima) prima.disabled = i <= 0;
        if (dopo) dopo.disabled = i >= slides.length - 1;
    };

    slider.addEventListener('scroll', segna, { passive: true });
    tab.forEach((t) => t.addEventListener('click', () => vaA(Number(t.dataset.capitolo) - 1)));
    prima?.addEventListener('click', () => vaA(corrente() - 1));
    dopo?.addEventListener('click', () => vaA(corrente() + 1));

    document.querySelectorAll('[data-open-tutorial]').forEach((bottone) =>
        bottone.addEventListener('click', () => {
            tutorial.showModal();

            // Riparte dal primo capitolo: la larghezza è nota solo dopo l'apertura.
            requestAnimationFrame(() => {
                slider.scrollTo({ left: 0 });
                segna();
            });
        }));

    tutorial.querySelectorAll('[data-close-tutorial]').forEach((bottone) =>
        bottone.addEventListener('click', () => tutorial.close()));

    // Il fondo scuro chiude: un click sul <dialog> stesso cade fuori dal pannello.
    tutorial.addEventListener('click', (evento) => {
        if (evento.target === tutorial) {
            tutorial.close();
        }
    });

    segna();

    // Arrivando dalla Home con `#tutorial`, si apre da solo.
    if (window.location.hash === '#tutorial') {
        tutorial.showModal();
        requestAnimationFrame(segna);
    }
}

// Ricerca del negozio

// Al focus la barra sale in cima, così i risultati (live) non finiscono sotto
// la tastiera. Delega su `document`: sopravvive ai ridisegni di Livewire.
document.addEventListener('focusin', (evento) => {
    const campo = evento.target.closest('[data-cerca]');

    // Col mouse non c'è tastiera a schermo: scorrere sarebbe solo uno scatto.
    if (!campo || !window.matchMedia('(pointer: coarse)').matches) return;

    // Ritardo: lascia aprire la tastiera prima di misurare dove scorrere.
    setTimeout(() => campo.scrollIntoView({ block: 'start', behavior: 'smooth' }), 300);
});

// Invio chiude la tastiera; la ricerca è già viva mentre si scrive.
document.addEventListener('keydown', (evento) => {
    if (evento.key === 'Enter' && evento.target.closest('[data-cerca]')) {
        evento.preventDefault();
        evento.target.blur();
    }
});

// `<details data-tendina>`: si chiudono al clic fuori e con Esc.
const tendineAperte = () => document.querySelectorAll('details[data-tendina][open]');

document.addEventListener('click', (evento) => {
    tendineAperte().forEach((tendina) => {
        if (!tendina.contains(evento.target)) tendina.open = false;
    });
});

document.addEventListener('keydown', (evento) => {
    if (evento.key !== 'Escape') return;

    tendineAperte().forEach((tendina) => {
        tendina.open = false;
        tendina.querySelector('summary')?.focus();
    });
});

// Sezioni a swipe: scheda del personaggio e mercato

// L'altezza segue la sezione a vista, o sotto resterebbe il vuoto delle più lunghe.
const sfogliabile = (scheda, tab) => {
    const sezioni = Array.from(scheda.children);

    // Il passo include il `gap` fra le sezioni.
    const passo = () => scheda.clientWidth + (parseFloat(getComputedStyle(scheda).columnGap) || 0);

    const indice = () => Math.round(scheda.scrollLeft / passo());

    const adattaAltezza = () => {
        const corrente = sezioni[Math.max(0, Math.min(sezioni.length - 1, indice()))];

        if (corrente) {
            scheda.style.height = `${corrente.offsetHeight}px`;
        }
    };

    const segna = () => {
        const i = indice();

        tab.forEach((t, j) => t.toggleAttribute('aria-current', j === i));

        // L'indirizzo segue la sezione: un refresh riapre dove si era.
        if (tab[i]?.dataset.url) {
            window.history.replaceState({}, '', tab[i].dataset.url);
        }

        if (tab[i]?.dataset.titolo) {
            document.title = tab[i].dataset.titolo;
        }

        adattaAltezza();
    };

    let attesa = null;
    scheda.addEventListener('scroll', () => {
        if (attesa) return;

        attesa = requestAnimationFrame(() => {
            attesa = null;
            segna();
        });
    }, { passive: true });

    const vaiA = (j) => scheda.scrollTo({ left: j * passo(), behavior: 'smooth' });

    tab.forEach((t, j) => t.addEventListener('click', () => vaiA(j)));

    // Su PC non c'è lo swipe: le frecce sfogliano le sezioni dalla barra.
    tab.forEach((t, j) => t.addEventListener('keydown', (evento) => {
        const verso = { ArrowRight: 1, ArrowLeft: -1 }[evento.key];
        if (!verso) return;

        evento.preventDefault();
        const prossima = (j + verso + tab.length) % tab.length;
        tab[prossima].focus();
        vaiA(prossima);
    }));

    // I componenti Livewire cambiano altezza da soli: si rimisura.
    const osserva = new ResizeObserver(() => adattaAltezza());
    sezioni.forEach((s) => osserva.observe(s));

    // Parte dalla sezione con aria-current dal server, senza animare l'altezza.
    const partenza = Math.max(0, tab.findIndex((t) => t.hasAttribute('aria-current')));
    scheda.style.transitionProperty = 'none';
    scheda.scrollLeft = partenza * passo();
    adattaAltezza();
    requestAnimationFrame(() => { scheda.style.transitionProperty = ''; });

    // Cambiando larghezza, la posizione in pixel non vale più: si riallinea.
    window.addEventListener('resize', () => {
        scheda.scrollLeft = indice() * passo();
        adattaAltezza();
    });
};

[['sheet-slider', '[data-sheet-tab]'], ['market-slider', '[data-market-tab]']].forEach(([id, linguette]) => {
    const scheda = document.getElementById(id);

    if (scheda) {
        sfogliabile(scheda, Array.from(document.querySelectorAll(linguette)));
    }
});

// Righe del bottino: si aprono una alla volta fino al limite.

const righeBottino = document.querySelector('[data-righe-bottino]');

if (righeBottino) {
    const aggiungi = document.querySelector('[data-aggiungi-oggetto]');
    const limite = document.querySelector('[data-limite-oggetti]');

    aggiungi?.addEventListener('click', () => {
        const chiuse = righeBottino.querySelectorAll('[data-riga-bottino][hidden]');

        if (chiuse.length === 0) return;

        chiuse[0].hidden = false;
        chiuse[0].querySelector('input')?.focus();

        if (chiuse.length === 1) {
            aggiungi.hidden = true;
            limite.hidden = false;
        }
    });
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

// App installabile (PWA)

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

// «Installa» compare solo dopo `beforeinstallprompt`, che iOS non manda.
let promptInstalla = null;

const pulsantiInstalla = () => document.querySelectorAll('[data-installa]');

window.addEventListener('beforeinstallprompt', (evento) => {
    evento.preventDefault();
    promptInstalla = evento;
    pulsantiInstalla().forEach((b) => (b.hidden = false));
});

document.addEventListener('click', (evento) => {
    if (!evento.target.closest('[data-installa]') || !promptInstalla) return;

    promptInstalla.prompt();
    promptInstalla.userChoice.finally(() => {
        promptInstalla = null;
        pulsantiInstalla().forEach((b) => (b.hidden = true));
    });
});

window.addEventListener('appinstalled', () => {
    promptInstalla = null;
    pulsantiInstalla().forEach((b) => (b.hidden = true));
});

// Su iOS, se non è già installata, si mostra l'istruzione manuale.
const iOS = /iphone|ipad|ipod/i.test(navigator.userAgent)
    || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
const giaInstallata = window.navigator.standalone === true
    || window.matchMedia('(display-mode: standalone)').matches;

if (iOS && !giaInstallata) {
    document.querySelectorAll('[data-ios-install]').forEach((el) => (el.hidden = false));
}
