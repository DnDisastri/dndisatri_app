<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Character;

/**
 * Le sezioni della scheda del personaggio.
 *
 * La scheda era **tredici riquadri in colonna**: cinque o sei schermate piene
 * per un mago di quinto livello, con le diciotto abilità piantate nel mezzo, e
 * tutto quello che veniva dopo raggiungibile solo scorrendo alla cieca.
 *
 * La divisione non segue la scheda di carta ma **cosa hai in mano in quel
 * momento**: si tira per colpire, si tira una prova, si lancia, si apre lo
 * zaino fra una scena e l'altra. Il resto — storia, privilegi, note — si legge
 * quando ci si ricorda che esiste, e non ha diritto di stare in mezzo.
 *
 * I punti ferita non stanno qui dentro: sono nell'intestazione, su tutte le
 * sezioni. Prendere danni è la cosa più frequente della serata, e se costasse
 * un cambio di sezione avremmo spostato la scomodità invece di toglierla.
 *
 * Il valore del caso è il pezzo di indirizzo, in italiano come tutte le rotte.
 */
enum SheetSection: string
{
    case Turn = 'turno';
    case Checks = 'prove';
    case Magic = 'magia';
    case Pack = 'zaino';
    case Story = 'storia';

    /**
     * La prima, quella che si apre entrando: `/personaggi/{pg}` senza altro.
     *
     * Ha una rotta sua e non un pezzo di indirizzo, così tutti i collegamenti
     * alla scheda che esistevano prima continuano a funzionare.
     */
    public const DEFAULT = self::Turn;

    public function label(): string
    {
        return match ($this) {
            self::Turn => 'Turno',
            self::Checks => 'Prove',
            self::Magic => 'Magia',
            self::Pack => 'Zaino',
            self::Story => 'Storia',
        };
    }

    /** L'indirizzo di questa sezione per un personaggio. */
    public function url(Character $character): string
    {
        return $this === self::DEFAULT
            ? route('characters.show', $character)
            : route('characters.section', [$character, $this->value]);
    }

    /**
     * Se questa sezione ha senso per questo personaggio.
     *
     * Un barbaro non ha niente da mettere sotto Magia, e una linguetta che si
     * apre sul vuoto è peggio di una linguetta che non c'è: se ne vede quattro
     * invece di cinque, e non si chiede cosa si sia perso.
     */
    public function fitsFor(Character $character): bool
    {
        return $this !== self::Magic || $character->casterType()->castsSpells();
    }

    /**
     * Se questa sezione è affare del solo giocatore (P14).
     *
     * La scheda di un altro si riduce **togliendo sezioni**, non cesellando
     * dentro i file: quello che non si deve vedere non viene disegnato e non
     * viene nemmeno caricato dal database. È lo stesso meccanismo di Magia per
     * un barbaro, ed è più semplice e si sbaglia meno di venti `@if` sparsi.
     *
     * **Ne restano due: la Storia e lo Zaino.** Di un compagno si legge chi è, e
     * cosa è disposto a scambiare. Non solo i numeri sono suoi — le
     * caratteristiche, l'oro — ma anche cosa sa fare: quali incantesimi ha
     * preparato oggi, con che arma attacca, quali privilegi ha scelto. Al tavolo
     * quelle cose si scoprono mentre si gioca, e leggersele in anticipo su una
     * pagina toglie la parte migliore. Chi vuole saperlo lo chiede.
     *
     * Lo Zaino è il caso diverso, e vale la pena dirlo qui: **la sezione resta,
     * il contenuto no.** A chi passa mostra la sola **vetrina** — gli oggetti che
     * il proprietario ha segnato «Scambierei» — perché quelli ce li ha messi lui
     * apposta perché li vedano. Il resto dello zaino e l'oro non ci arrivano
     * nemmeno: li filtra il controllore quando carica la relazione.
     */
    public function isPrivate(): bool
    {
        return ! in_array($this, self::PUBBLICHE, true);
    }

    /**
     * Le sezioni che vede anche chi passa, **nell'ordine in cui le vede**.
     *
     * L'ordine qui non è quello dei casi, ed è voluto: la scheda di un altro si
     * apre per sapere **chi è**, e la vetrina è un di più. Nella scheda intera
     * la Storia è invece l'ultima, perché lì il primo posto ce l'ha il Turno —
     * chi gioca il proprio personaggio non lo apre per rileggersi la storia.
     */
    public const PUBBLICHE = [self::Story, self::Pack];

    /**
     * Le relazioni che servono a **questa** sezione, e non alle altre.
     *
     * Prima se ne caricavano sei per disegnare tutto in una volta. Adesso ogni
     * sezione porta il suo: fuori produzione `preventLazyLoading` fa da
     * guardia, quindi una dimenticanza qui rompe la pagina invece di generare
     * in silenzio una query per riga (§8.6 del brief).
     *
     * `classes` e `itemEffects` ci sono sempre: la prima serve all'intestazione
     * — un multiclasse va scritto per intero — e la seconda ai punteggi
     * efficaci, che l'intestazione usa per la classe armatura.
     *
     * @return list<string>
     */
    public function relations(): array
    {
        $sempre = ['user', 'classes', 'itemEffects', 'items'];

        return match ($this) {
            // «Turno» non elenca le armi: elenca **cosa puoi fare**, e per
            // farlo gli servono le armi, i trucchetti e i talenti insieme.
            self::Turn => [...$sempre, 'weapons', 'spells', 'feats'],
            self::Checks => $sempre,
            self::Magic => [...$sempre, 'spells'],
            self::Pack => $sempre,
            self::Story => [...$sempre, 'feats'],
        };
    }

    /**
     * Le sezioni che questo personaggio mostra davvero a questo lettore.
     *
     * Due tagli di seguito e due ragioni diverse: `$tutte` sceglie **quali**
     * sezioni — tutte, o le sole pubbliche — e `fitsFor` toglie quelle che
     * questo personaggio non ha, come Magia per un barbaro.
     *
     * La prima della lista è anche quella che si apre entrando: per chi la
     * possiede è il Turno, per chi passa è la Storia.
     *
     * @param  bool  $tutte  Se il lettore ha diritto anche alle sezioni private
     *                       — il proprietario e chi conduce (`viewFullSheet`).
     * @return list<self>
     */
    public static function forCharacter(Character $character, bool $tutte = true): array
    {
        return array_values(array_filter(
            $tutte ? self::cases() : self::PUBBLICHE,
            fn (self $sezione) => $sezione->fitsFor($character),
        ));
    }
}
