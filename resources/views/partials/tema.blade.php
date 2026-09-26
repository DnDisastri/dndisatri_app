{{--
    La scelta del tema, applicata **prima che la pagina si dipinga**.

    Deve stare qui, in linea nell'intestazione, e non nel bundle: uno script
    con `defer` gira dopo il primo disegno, quindi chi ha scelto il tema scuro
    vedrebbe un lampo bianco a ogni pagina. È l'unico javascript in linea di
    tutta l'applicazione, e questa è la ragione.

    Nessuna scelta salvata significa **automatico**: non si scrive niente
    sull'elemento e comanda `prefers-color-scheme`, cioè il sistema operativo.
    È il motivo per cui l'attributo si mette solo per «chiaro» e «scuro» — un
    `data-theme="auto"` scavalcherebbe la media query invece di lasciarla fare.
--}}
<script>
    (function () {
        try {
            var scelta = localStorage.getItem('dndisastri:tema');

            if (scelta === 'dark' || scelta === 'light') {
                document.documentElement.dataset.theme = scelta;
            }
        } catch (e) {
            // In navigazione privata `localStorage` può tirare un'eccezione al
            // solo leggerlo. Non è un caso da gestire: si resta sull'automatico,
            // che è la scelta giusta per chi non ne ha espressa una.
        }
    })();
</script>
