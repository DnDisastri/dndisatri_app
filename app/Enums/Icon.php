<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Icona;
use Filament\Support\Contracts\ScalableIcon;
use Filament\Support\Enums\IconSize;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use ToneGabes\Filament\Icons\Enums\Weight;

/**
 * Tutte le icone dell'applicazione, in un posto solo.
 *
 * I casi hanno il nome della **cosa** — `Campaigns`, `Approve` — e non del
 * disegno: se domani le campagne meritassero un'icona diversa si cambia una
 * riga qui e cambia ovunque, senza cercare `map-trifold` in trenta file.
 *
 * È anche l'unico modo per accorgersi che due schermate chiamavano la stessa
 * cosa in due modi: gli eventi erano `confetti` sul sito e `calendar-dots` nel
 * Pannello, e nessuno poteva vederlo finché le due righe stavano lontane.
 *
 * Si usa in tre posti:
 *
 * - nelle viste: `<x-icona :is="Icon::Notifications" />`
 * - in Filament: `$navigationIcon = Icon::Campaigns`
 * - dove serve il nome grezzo: `Icon::Campaigns->blade()` → `phosphor-map-trifold`
 *
 * Il valore del caso è una **chiave nostra**, non il nome del disegno: così due
 * cose diverse possono condividere la stessa icona — lo scudo vale per la
 * Gilda e per la Classe Armatura — cosa che un enum non permetterebbe se il
 * valore fosse il disegno. Il disegno si sceglie in `phosphor()`, per
 * confronto, quindi un nome inesistente non compila nemmeno.
 */
enum Icon: string implements Icona, ScalableIcon
{
    // La barra di navigazione del sito.
    case Campaigns = 'campaigns';
    case Ledger = 'ledger';
    case Characters = 'characters';
    case Market = 'market';
    case Events = 'events';

    // L'intestazione.
    case Notifications = 'notifications';
    case Menu = 'menu';

    // Il menù dell'utente.
    case Profile = 'profile';
    case Panel = 'panel';
    case Logout = 'logout';

    // Le sezioni del Pannello che non sono già qui sopra.
    case Builds = 'builds';
    case Users = 'users';
    case Quests = 'quests';
    case Shop = 'shop';
    case Listings = 'listings';
    case Trades = 'trades';
    case Sessions = 'sessions';
    case DmRequests = 'dm-requests';
    case Bestiary = 'bestiary';
    case News = 'news';
    case Maps = 'maps';
    case Proposals = 'proposals';

    // Le azioni.
    case Approve = 'approve';
    case Reject = 'reject';
    case Archive = 'archive';
    // Mettere via e rimettere in lista: l'archivio personale di notifiche e
    // richieste, diverso dall'`Archive` del Pannello (che conclude una campagna).
    case Stash = 'stash';
    case Unstash = 'unstash';
    case Featured = 'featured';
    case NotFeatured = 'not-featured';
    case Favorite = 'favorite';
    case NotFavorite = 'not-favorite';
    case Close = 'close';

    // I segni dentro le pagine.
    case Guild = 'guild';
    case Fallen = 'fallen';
    case ArmorClass = 'armor-class';
    case HitPoints = 'hit-points';
    case Gold = 'gold';
    case Proficient = 'proficient';
    case Expert = 'expert';
    case GoTo = 'go-to';
    case Discover = 'discover';
    case Back = 'back';

    // Il Turno: i costi, i sotto-gruppi e i tipi di tiro del cheat sheet.
    case Action = 'action';
    case BonusAction = 'bonus-action';
    case Reaction = 'reaction';
    case Passive = 'passive';
    case Attacks = 'attacks';
    case Cantrips = 'cantrips';
    case Privileges = 'privileges';
    case CastSpell = 'cast-spell';
    case General = 'general';
    case SpellAttack = 'spell-attack';
    case SpellSave = 'spell-save';
    case NoRoll = 'no-roll';
    case Upcast = 'upcast';
    case Talents = 'talents';

    // Il menù ⋯ della scheda: le proposte del giocatore e il suo registro.
    case Edit = 'edit';
    case LevelUp = 'level-up';
    case Loot = 'loot';
    case MagicItem = 'magic-item';
    case CharacterLedger = 'character-ledger';
    case Search = 'search';
    case Expand = 'expand';
    case Warnings = 'warnings';
    case Supervision = 'supervision';
    case Rank = 'rank';

    /**
     * Il disegno vero e proprio.
     */
    public function phosphor(): Phosphor
    {
        return match ($this) {
            self::Campaigns => Phosphor::FlagBannerFold,
            self::Ledger => Phosphor::BookBookmark,
            self::Characters => Phosphor::IdentificationBadge,
            self::Market => Phosphor::HandCoins,
            self::Events => Phosphor::BookmarkSimple,
            self::Notifications => Phosphor::Bell,
            self::Menu => Phosphor::DotsThreeBold,

            // Il profilo è la persona, il Pannello sono le sue leve, uscire è
            // la porta: tre segni del menù dell'utente.
            self::Profile => Phosphor::UserCircle,
            self::Panel => Phosphor::SlidersHorizontal,
            self::Logout => Phosphor::SignOut,

            self::Builds => Phosphor::Sparkle,
            self::Users => Phosphor::Users,
            self::Quests => Phosphor::SealQuestion,
            self::Shop => Phosphor::Storefront,
            // Le tre porte del mercato, una per mestiere: la bottega che vende,
            // il foglio degli annunci, le due frecce che si scambiano il posto.
            self::Listings => Phosphor::Article,
            self::Trades => Phosphor::ArrowsLeftRight,
            self::Sessions => Phosphor::CalendarDots,
            self::DmRequests => Phosphor::ShieldCheck,
            // Il bestiario: il fantasma, il mostro per antonomasia. Il teschio
            // è già dei caduti, e un mostro non è un eroe morto.
            self::Bestiary => Phosphor::Ghost,
            self::News => Phosphor::Newspaper,
            self::Maps => Phosphor::MapTrifold,
            self::Proposals => Phosphor::Tray,

            self::Approve => Phosphor::CheckCircle,
            self::Reject => Phosphor::XCircle,
            self::Archive => Phosphor::Archive,
            // La freccia che entra nella scatola e quella che ne esce: mettere
            // via una voce dalla lista, e ripescarla dall'archivio.
            self::Stash => Phosphor::BoxArrowDown,
            self::Unstash => Phosphor::BoxArrowUp,
            self::Featured => Phosphor::Sparkle,
            self::NotFeatured => Phosphor::Minus,
            // La stella piena e la stella vuota sono lo stesso disegno in due
            // stati, ed è quello che serve: la stella dei preferiti si preme, e
            // premendola deve cambiare senza spostare niente attorno.
            self::Favorite => Phosphor::StarFill,
            self::NotFavorite => Phosphor::Star,
            // Chiudere non è rifiutare: la crocetta di un riquadro lo fa
            // sparire e basta, mentre `Reject` vuol dire «no» a qualcosa che
            // qualcuno ha chiesto. Due gesti, due casi.
            self::Close => Phosphor::X,

            // Lo stesso scudo per due cose diverse: la gilda e la difesa.
            self::Guild => Phosphor::ShieldCheckered,
            self::ArmorClass => Phosphor::Shield,
            self::Fallen => Phosphor::Skull,
            self::HitPoints => Phosphor::Heart,
            // Il mercato è il mucchio di monete, l'oro di un personaggio è una
            // moneta sola: sono due cose e si vedono diverse.
            self::Gold => Phosphor::Coin,
            // Competente e Esperto sono due gradini della stessa scala: il
            // pieno e poi la stella, così si distinguono anche di sfuggita.
            self::Proficient => Phosphor::CircleFill,
            self::Expert => Phosphor::StarFill,
            // Il Turno. I quattro costi, i sotto-gruppi, i tipi di tiro: le
            // icone le ha scelte lei. Reazione è il triangolo d'avviso (succede
            // fuori dal tuo turno) e il tiro salvezza è lo scudo — gli stessi
            // disegni di Warnings e ArmorClass, ma qui sono un'altra cosa.
            self::Action => Phosphor::HandFist,
            self::BonusAction => Phosphor::HandGrabbing,
            self::Reaction => Phosphor::Warning,
            self::Passive => Phosphor::HandPalm,
            self::Attacks => Phosphor::Sword,
            self::Cantrips => Phosphor::MagicWand,
            self::Privileges => Phosphor::Trophy,
            self::CastSpell => Phosphor::PersonSimpleThrow,
            self::General => Phosphor::UsersFour,
            self::SpellAttack => Phosphor::DiceFive,
            self::SpellSave => Phosphor::Shield,
            self::NoRoll => Phosphor::Prohibit,
            self::Upcast => Phosphor::ArrowUp,
            self::Talents => Phosphor::Ranking,

            // Il menù ⋯ della scheda: le icone le ha scelte lei. La bacchetta è
            // la stessa dei trucchetti, la moneta la stessa dell'oro — disegni
            // condivisi, cose diverse.
            self::Edit => Phosphor::PencilSimple,
            self::LevelUp => Phosphor::ArrowFatUp,
            self::Loot => Phosphor::Coin,
            self::MagicItem => Phosphor::MagicWand,
            self::CharacterLedger => Phosphor::Notebook,

            self::GoTo => Phosphor::ArrowRight,
            // «Vai» e «scopri» non sono la stessa cosa: la prima porta dove
            // stavi già andando, la seconda è un invito ad aprire qualcosa che
            // non hai chiesto. La freccia dritta e quella in diagonale dicono
            // esattamente questa differenza, ed è il motivo per cui sono due
            // casi e non uno.
            self::Discover => Phosphor::ArrowUpRight,
            // Indietro: la freccia guarda da dove si è venuti, ed è l'unica
            // che punta a sinistra in tutta l'applicazione.
            self::Back => Phosphor::ArrowLeft,
            self::Search => Phosphor::MagnifyingGlass,
            // «Qui sotto c'è dell'altro»: la punta gira quando si apre, ed è
            // l'unico segno che distingue una riga che si apre da una che è
            // già tutto quello che ha da dire.
            self::Expand => Phosphor::CaretDown,
            // Il triangolo e non il divieto: un richiamo è un
            // avvertimento con una via d'uscita, non una porta chiusa.
            self::Warnings => Phosphor::Warning,
            // L'occhio: la vigilanza è guardare quello che passa,
            // non sbarrare la strada.
            self::Supervision => Phosphor::Eye,
            // Il medaglione del grado d'avventuriero: il colore lo mette chi lo
            // usa, uno per metallo (legno, bronzo, argento, oro, platino).
            self::Rank => Phosphor::MedalMilitary,
        };
    }

    /**
     * Il peso del tratto, per tutte le icone insieme.
     *
     * **Duotone non è a due colori:** è la stessa sagoma della regular più una
     * forma piena sotto, all'opacità del 20%, e tutte e due prendono
     * `currentColor`. Quindi è un colore solo a due intensità, e il contorno
     * esterno resta identico — cambiando peso non si sposta niente nel layout.
     *
     * Si cambia qui e cambia ovunque, Pannello compreso.
     */
    private const PESO = Weight::Duotone;

    /**
     * Il nome per blade-icons: `phosphor-bell-duotone`.
     */
    public function blade(): string
    {
        $nome = $this->phosphor()->getLabel();

        /*
         * Un'icona che ha già un peso suo se lo tiene: i pesi di Phosphor si
         * escludono a vicenda, non si sommano. Competente ed Esperto sono un
         * cerchio e una stella **pieni** proprio per distinguersi di sfuggita,
         * e il menù è un `bold` perché tre puntini sottili non si vedono.
         *
         * Non è una questione di gusto: `dots-three-bold-duotone` non esiste
         * come file, e blade-icons su un nome che non trova non ripiega su
         * niente — tira un'eccezione e la pagina va in errore. La condizione si
         * legge dalla variante e non da un elenco scritto a mano, che il giorno
         * che qualcuno aggiunge un'icona pesata resterebbe indietro in
         * silenzio.
         */
        foreach (Weight::cases() as $peso) {
            if ($peso !== Weight::Regular && str_ends_with($this->phosphor()->value, '-'.$peso->value)) {
                return $nome;
            }
        }

        return self::PESO === Weight::Regular ? $nome : $nome.'-'.self::PESO->value;
    }

    /**
     * Filament chiede l'icona in questa forma. La misura non ci cambia niente:
     * Phosphor ha un disegno per peso, non uno per dimensione.
     */
    public function getIconForSize(IconSize $size): string
    {
        return $this->blade();
    }
}
