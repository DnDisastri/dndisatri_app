<?php

namespace Database\Seeders;

use App\Enums\ListingStatus;
use App\Enums\PendingChangeStatus;
use App\Enums\QuestDifficulty;
use App\Enums\QuestSeatStatus;
use App\Enums\TradeStatus;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\DmRequest;
use App\Models\Event;
use App\Models\GameSession;
use App\Models\Map;
use App\Models\MarketListing;
use App\Models\Monster;
use App\Models\PendingChange;
use App\Models\Post;
use App\Models\Quest;
use App\Models\Trade;
use App\Models\User;
use Database\Seeders\Support\Placeholder;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Il database dimostrativo: **almeno un dato per ogni cosa che si vede**.
 *
 * Non serve a provare la logica — per quella ci sono i test — ma a guardare le
 * pagine piene. Una pagina vuota non si può giudicare: gli spazi sembrano
 * giusti, i titoli sembrano leggibili, e i problemi si scoprono il giorno in
 * cui arrivano i dati veri.
 *
 * Per questo i dati non sono casuali ma **scelti**: due tavoli la stessa sera
 * (la Home li deve mostrare entrambi), una quest al completo e una con posto,
 * una campagna conclusa in una season vecchia, un caduto morto in una serata
 * precisa. Ognuno di questi copre un caso che le pagine trattano a parte.
 *
 * Si lancia con `php artisan db:seed --class=DemoSeeder`, ed è **ripetibile**:
 * cancella i suoi dati e li rifà, invece di accumularne una copia a ogni giro.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoSeeder crea dati finti: non va eseguito in produzione.');
        }

        $this->call([RoleSeeder::class, MarketSeeder::class, DevUserSeeder::class, BuildSeeder::class]);

        $this->svuota();

        $giocatori = $this->giocatori();
        $dm = User::role(User::ROLE_DM)->orderBy('id')->get();

        $personaggi = $this->personaggi($giocatori);

        $campagne = $this->campagne($dm);
        $sessioni = $this->sessioni($campagne, $personaggi);
        $this->incarichi($campagne, $giocatori);
        $this->mappe($campagne);

        $this->caduto($giocatori->last(), $sessioni->first());

        $this->eventi();
        $this->bestiario($dm->first());
        $this->news($dm->first());
        $this->mercato($personaggi);
        $this->richieste($personaggi, $dm->first());

        $this->command?->newLine();
        $this->command?->info('Database dimostrativo pronto.');
        $this->command?->line('  Entra con giocatore1@dndisastri.test, oppure con il tuo account.');
    }

    /**
     * Ripetibile: si cancella quello che questo seeder crea, e solo quello.
     *
     * L'ordine è quello delle dipendenze — le presenze prima delle serate, le
     * serate prima delle campagne — perché le chiavi esterne non perdonano.
     */
    private function svuota(): void
    {
        PendingChange::query()->delete();
        DmRequest::query()->delete();
        Trade::query()->delete();
        MarketListing::query()->delete();

        // I caduti puntano a una serata: si sganciano prima di cancellarla.
        Character::query()->update(['died_in_session_id' => null]);

        GameSession::query()->each(fn (GameSession $s) => $s->attendees()->detach());
        GameSession::query()->delete();

        Quest::query()->each(fn (Quest $q) => $q->participants()->detach());
        Quest::query()->delete();

        Map::query()->delete();
        Campaign::query()->delete();
        Event::query()->delete();
        Post::query()->delete();
    }

    /** Sei giocatori: abbastanza perché la Gilda sembri una gilda. */
    private function giocatori()
    {
        $esistenti = User::role(User::ROLE_PLAYER)->orderBy('id')->get();

        if ($esistenti->count() < 6) {
            User::factory()->player()->count(6 - $esistenti->count())->create();
        }

        return User::role(User::ROLE_PLAYER)->orderBy('id')->get();
    }

    /**
     * I personaggi vivi.
     *
     * Chi ce l'ha già se lo tiene — la scheda può avere ore di lavoro dentro —
     * e si riempie solo il vuoto.
     */
    private function personaggi($giocatori)
    {
        $ritratti = [
            ['Thalia Ventochiaro', 'Ranger', 'Cacciatrice', 'Elfo', 4,
                'Cresciuta fra i boschi di confine, dice di non aver mai perso una pista. Non è vero, ma nessuno lo sa.'],
            ['Padre Ismaele', 'Chierico', 'Vita', 'Umano', 4,
                'Ha lasciato il tempio dopo una discussione che nessuno gli ha mai spiegato. Prega ancora, per abitudine.'],
            ['Nix', 'Ladro', 'Furfante', 'Halfling', 3,
                'Sostiene di essere in città per affari. Gli affari cambiano ogni volta che glielo si chiede.'],
            ['Ordo Pietrafonda', 'Guerriero', 'Campione', 'Nano', 5,
                'Vent\'anni di guardia a un cancello da cui non è mai passato nessuno. Poi il cancello è stato abbattuto.'],
        ];

        foreach ($giocatori as $indice => $giocatore) {
            if ($giocatore->characters()->alive()->exists()) {
                continue;
            }

            $ritratto = $ritratti[$indice % count($ritratti)] ?? null;

            if ($ritratto === null) {
                continue;
            }

            [$nome, $classe, $sottoclasse, $specie, $livello, $storia] = $ritratto;

            // Il nome è univoco nella Gilda: se c'è già si salta, invece di
            // fare esplodere il seeder su un vincolo del database.
            if (Character::where('name', $nome)->exists()) {
                continue;
            }

            Character::factory()->ownedBy($giocatore)->create([
                'name' => $nome,
                'class' => $classe,
                'subclass' => $sottoclasse,
                'race' => $specie,
                'level' => $livello,
                'story' => $storia,
                'photo_path' => Placeholder::make('personaggi', $nome, 600, 600),
                'hp_max' => 8 + $livello * 6,
                'hp_current' => 8 + $livello * 6,
                'gp' => 40 * $livello,
                'skills' => ['perception' => 'proficient', 'stealth' => 'expert'],
                'saving_throws' => ['dex' => true],
            ]);
        }

        return Character::alive()->with('user')->orderBy('id')->get();
    }

    /** Tre campagne: due attive nella season in corso, una conclusa in quella prima. */
    private function campagne($dm)
    {
        $primo = $dm->first();
        $secondo = $dm->get(1) ?? $primo;

        $definizioni = [
            [$primo, 2, 'Le Rovine di Valcupa', null,
                'Sotto la città vecchia c\'è una seconda città, e qualcuno ci abita ancora.',
                'Berengario il Grigio', 'Tiene il banco dei pegni all\'angolo della piazza. Sa cosa succede sottoterra, e lo racconta a rate.'],
            [$secondo, 2, 'La Rotta del Sale', null,
                'Sei carovane partite, quattro arrivate. Il committente vuole sapere dove sono finite le altre due.',
                'Capitana Vess', 'Comanda il porto da otto anni. Parla poco e paga puntuale, il che nel porto è una reputazione.'],
            [$primo, 1, 'L\'Inverno di Karrenmoor', now()->subMonths(4),
                'Il freddo è arrivato tre mesi prima del previsto, e non se n\'è più andato.',
                'Anziana Mirith', 'Era la sola a dire che sarebbe successo. Adesso nessuno vuole più sentirla parlare.'],
        ];

        foreach ($definizioni as [$conduce, $season, $titolo, $conclusa, $descrizione, $capogilda, $chiEra]) {
            Campaign::create([
                'title' => $titolo,
                'slug' => \Illuminate\Support\Str::slug($titolo),
                'description' => $descrizione,
                'cover_path' => Placeholder::make('campagne', $titolo),
                'season' => $season,
                'quest_giver' => $capogilda,
                'quest_giver_description' => $chiEra,
                'quest_giver_photo' => Placeholder::make('capigilda', $capogilda, 400, 400),
                'dm_id' => $conduce?->getKey(),
                'ended_at' => $conclusa,
            ]);
        }

        return Campaign::orderBy('id')->get();
    }

    /**
     * Le serate: passate col resoconto, e due future **la stessa sera**.
     *
     * Quel dettaglio non è decorativo: la Home dice «i prossimi tavoli» al
     * plurale proprio per questo caso, e senza due tavoli contemporanei non si
     * può vedere se funziona.
     */
    private function sessioni($campagne, $personaggi)
    {
        $seraProssima = now()->addDays(6)->setTime(21, 0);

        foreach ($campagne as $indice => $campagna) {
            $numero = 1;

            foreach ([5, 3, 1] as $settimaneFa) {
                $sessione = GameSession::create([
                    'campaign_id' => $campagna->getKey(),
                    'number' => $numero++,
                    'title' => $this->titoloSerata($indice, $settimaneFa),
                    'played_at' => now()->subWeeks($settimaneFa)->setTime(21, 0),
                    'recap' => $this->resoconto($indice, $settimaneFa),
                    'recap_written_by' => $campagna->dm_id,
                    'recap_written_at' => now()->subWeeks($settimaneFa)->addDay(),
                ]);

                // Le presenze, col personaggio: è la differenza fra «c'era
                // Marco» e «c'era Grimm», e le pagine mostrano la seconda.
                foreach ($personaggi->take(4) as $personaggio) {
                    $sessione->attendees()->attach($personaggio->user_id, [
                        'character_id' => $personaggio->getKey(),
                    ]);
                }
            }

            if ($campagna->isActive()) {
                /*
                 * **Senza titolo, ed è la verità.** Una serata che deve ancora
                 * essere giocata di solito non ha un nome: il nome glielo dà
                 * quello che succede. Prima qui c'era scritto «Da giocare», e
                 * usciva «Sessione 4 — Da giocare», che sembrava uno stato e
                 * invece era un titolo — per giunta le stesse parole della
                 * pillola vera che dice se una serata è passata o no.
                 */
                GameSession::create([
                    'campaign_id' => $campagna->getKey(),
                    'number' => $numero,
                    'played_at' => $seraProssima,
                ]);
            }
        }

        return GameSession::past()->orderByDesc('played_at')->get();
    }

    private function titoloSerata(int $campagna, int $settimane): string
    {
        return [
            [5 => 'Il pozzo', 3 => 'La porta murata', 1 => 'Quello che c\'era sotto'],
            [5 => 'Partenza dal porto', 3 => 'La quarta carovana', 1 => 'Il carico sbagliato'],
            [5 => 'La prima nevicata', 3 => 'Il valico chiuso', 1 => 'Karrenmoor'],
        ][$campagna % 3][$settimane];
    }

    private function resoconto(int $campagna, int $settimane): string
    {
        return [
            [
                5 => 'Abbiamo trovato il pozzo dietro il magazzino, e ci siamo calati. Trenta metri, poi un corridoio che non era nelle mappe. Ordo ha insistito per andare avanti.',
                3 => 'La porta era murata dall\'interno, il che è un dettaglio che nessuno ha voluto commentare. Ci abbiamo messo due ore ad aprirla. Non ne è valsa la pena.',
                1 => 'C\'era una seconda città. Non rovine: una città. Con le luci accese. Siamo tornati su senza dire niente a nessuno, e adesso non sappiamo a chi dirlo.',
            ],
            [
                5 => 'Partiti col favore della marea. La capitana ci ha dato una lista di nomi e nessuna spiegazione, come al solito.',
                3 => 'Trovata la quarta carovana a due giorni dalla strada, tutta intera. I carri pieni, i cavalli vivi, nessuno a bordo. Nix ha voluto controllare due volte.',
                1 => 'Il carico non era sale. Padre Ismaele ha detto una cosa che nessuno ha capito e poi non ha più parlato per il resto della serata.',
            ],
            [
                5 => 'Neve a settembre. I contadini davano la colpa alla vecchia, la vecchia dava la colpa a qualcos\'altro.',
                3 => 'Il valico si è chiuso alle nostre spalle. Da lì in poi non era più una spedizione, era una fuga.',
                1 => 'Karrenmoor era vuota da mesi. Abbiamo passato la notte nella sala grande e alla mattina siamo tornati indietro. La campagna finisce qui.',
            ],
        ][$campagna % 3][$settimane];
    }

    /** Incarichi: uno al completo, uno con posto, uno completato, uno abbandonato. */
    private function incarichi($campagne, $giocatori): void
    {
        foreach ($campagne as $campagna) {
            if (! $campagna->isActive()) {
                // Una campagna conclusa ha solo archivio: aperti non ne ha
                // più, e mostrarne uno sarebbe una promessa che non mantiene.
                Quest::factory()->inCampaign($campagna)->completed()->create([
                    'title' => 'Riportare i registri al comune',
                    'slug' => 'registri-al-comune',
                    'rewards' => '150 mo',
                    'difficulty' => QuestDifficulty::Facile,
                ]);

                Quest::factory()->inCampaign($campagna)->closed()->create([
                    'title' => 'Cercare il figlio del fabbro',
                    'slug' => 'figlio-del-fabbro',
                    'rewards' => 'La gratitudine del fabbro',
                    'difficulty' => QuestDifficulty::Media,
                ]);

                continue;
            }

            // Prenotata ma non ancora confermata: è lo stato in cui una quest
            // passa la maggior parte della sua vita, e va guardato pieno.
            $conPosto = Quest::factory()->inCampaign($campagna)->slots(5)->create([
                'title' => 'Scortare la carovana fino al guado',
                'slug' => 'carovana-guado-'.$campagna->getKey(),
                'rewards' => '200 mo e una pozione di cura',
                'difficulty' => QuestDifficulty::Media,
                'setting' => 'Strada bassa, due giorni di cammino',
                'min_participants' => 3,
            ]);
            $this->prenota($conPosto, $giocatori->take(2), QuestSeatStatus::Booked);

            // Piena, con la serata già confermata e due in lista d'attesa:
            // serve a vedere il pescaggio senza doverlo costruire a mano.
            $pieno = Quest::factory()->inCampaign($campagna)->slots(2)->create([
                'title' => 'Scendere di nuovo nel pozzo',
                'slug' => 'di-nuovo-nel-pozzo-'.$campagna->getKey(),
                'rewards' => 'Quello che si trova',
                'difficulty' => QuestDifficulty::Difficile,
                'min_participants' => 2,
            ]);
            $this->prenota($pieno, $giocatori->take(2), QuestSeatStatus::Confirmed);
            $this->prenota($pieno, $giocatori->slice(2, 2), QuestSeatStatus::Waiting);

            // La serata dichiarata sta **sulla quest**, e va scritta anche qui:
            // dei posti confermati senza la data la pagina dice «manca solo che
            // il dungeon master lo dica» mentre i posti dicono di sì. Sono due
            // fatti distinti di proposito — se si ritirassero tutti, la serata
            // resterebbe dichiarata — e i dati di prova devono rispettarlo.
            $pieno->forceFill(['night_confirmed_at' => now()->subDay()])->save();

            Quest::factory()->inCampaign($campagna)->completed()->create([
                'title' => 'Consegnare la lettera sigillata',
                'slug' => 'lettera-sigillata-'.$campagna->getKey(),
                'rewards' => '80 mo',
                'difficulty' => QuestDifficulty::Facile,
                // Un incarico concluso senza il racconto è mezza pagina vuota,
                // ed è metà del motivo per cui P19 esiste anche dopo la fine.
                'outcome_notes' => 'La lettera è arrivata, ma non al destinatario giusto. '
                    .'Il sigillo era già rotto quando l\'abbiamo presa.',
            ]);
        }
    }

    /** Prenota una fila di giocatori a una quest, tutti nello stesso stato. */
    private function prenota(Quest $quest, $giocatori, QuestSeatStatus $stato): void
    {
        foreach ($giocatori as $indice => $giocatore) {
            $quest->participants()->attach($giocatore, [
                'status' => $stato->value,
                // Sfalsati di un minuto: la lista d'attesa ha un ordine, e con
                // lo stesso istante per tutti non si vedrebbe.
                'joined_at' => now()->subMinutes(10 - $indice),
                'decided_at' => $stato === QuestSeatStatus::Confirmed ? now() : null,
            ]);
        }
    }

    private function mappe($campagne): void
    {
        foreach ($campagne as $campagna) {
            foreach (['Mappa della regione', 'Pianta del sotterraneo'] as $titolo) {
                Map::create([
                    'campaign_id' => $campagna->getKey(),
                    'title' => $titolo,
                    'image_path' => Placeholder::make('mappe', $titolo, 800, 800),
                ]);
            }
        }

        Map::create([
            'campaign_id' => null,
            'title' => 'Il mondo conosciuto',
            'description' => 'Vale per tutte le campagne.',
            'image_path' => Placeholder::make('mappe', 'Il mondo conosciuto', 1000, 700),
        ]);
    }

    /**
     * Un caduto, morto in una serata precisa.
     *
     * Il collegamento alla serata è il punto: la Hall of Fallen Heroes esiste
     * per raccontare come sono andate le cose, e senza la serata resterebbe
     * una data.
     */
    private function caduto(User $giocatore, ?GameSession $sessione): void
    {
        if (Character::where('name', 'Corvo')->exists()) {
            return;
        }

        Character::factory()->ownedBy($giocatore)->create([
            'name' => 'Corvo',
            'class' => 'Stregone',
            'subclass' => 'Stirpe Draconica',
            'race' => 'Tiefling',
            'level' => 3,
            'story' => 'Non ha mai detto da dove venisse. Alla fine non ha fatto in tempo.',
            'photo_path' => Placeholder::make('personaggi', 'Corvo', 600, 600),
            'died_at' => $sessione?->played_at ?? now()->subWeeks(1),
            'death_story' => 'È rimasto indietro per tenere aperta la porta. L\'ha tenuta aperta.',
            'died_in_session_id' => $sessione?->getKey(),
            'hp_max' => 20,
            'hp_current' => 0,
        ]);
    }

    /** Eventi: tre in arrivo, due passati, uno programmato che non si deve vedere. */
    private function eventi(): void
    {
        $definizioni = [
            ['Torneo di una notte', now()->addDays(9)->setTime(20, 30), 'Sala grande della gilda',
                'Sei tavoli, un\'ora e mezza ciascuno, un vincitore. Portate i dadi che vi portano fortuna.'],
            ['One-shot: La Locanda Chiusa', now()->addWeeks(3)->setTime(21, 0), 'Da Marta',
                'Livello 3, personaggi pregenerati. Serve solo presentarsi.'],
            ['Cena di fine season', now()->addWeeks(5)->setTime(20, 0), 'Trattoria del Ponte',
                'Si mangia, si beve e si raccontano le morti più stupide dell\'anno.'],
        ];

        foreach ($definizioni as [$titolo, $quando, $dove, $descrizione]) {
            Event::create([
                'title' => $titolo,
                'slug' => \Illuminate\Support\Str::slug($titolo),
                'description' => $descrizione,
                'cover_path' => Placeholder::make('eventi', $titolo),
                'starts_at' => $quando,
                'ends_at' => $quando->copy()->addHours(4),
                'location' => $dove,
                'published_at' => now()->subWeek(),
            ]);
        }

        Event::factory()->past()->create([
            'title' => 'Il raduno di primavera',
            'slug' => 'raduno-di-primavera',
            'cover_path' => Placeholder::make('eventi', 'Il raduno di primavera'),
        ]);

        Event::factory()->past()->create([
            'title' => 'Maratona di Capodanno',
            'slug' => 'maratona-di-capodanno',
        ]);

        // Programmato: scritto, ma non ancora visibile. Serve a controllare che
        // resti invisibile — è il motivo per cui esiste la programmazione.
        Event::factory()->create([
            'title' => 'La sorpresa che non si deve vedere',
            'slug' => 'la-sorpresa',
            'published_at' => now()->addWeeks(2),
            'starts_at' => now()->addWeeks(6),
        ]);
    }

    /**
     * Un pugno di mostri nel bestiario, con lo statblock: bastano a vedere il
     * pescaggio dal tracker e il modale esteso. Se ce n'è già — magari scritti
     * da un DM — non si tocca niente.
     */
    private function bestiario(?User $autore): void
    {
        if (Monster::query()->exists()) {
            return;
        }

        $mostri = [
            ['Goblin', 7, 15, '9 m',
                [['nome' => 'Scimitarra', 'bonus' => '+4', 'danni' => '1d6+2'], ['nome' => 'Arco corto', 'bonus' => '+4', 'danni' => '1d6+2']],
                'Fuga astuta: scatta o si disimpegna come azione bonus a ogni turno.'],
            ['Hobgoblin', 11, 18, '9 m',
                [['nome' => 'Spada lunga', 'bonus' => '+3', 'danni' => '1d8+1']],
                'Vantaggio marziale: +2d6 danni una volta per turno se un alleato è entro 1,5 m dal bersaglio.'],
            ['Orso bruno', 34, 11, '12 m',
                [['nome' => 'Morso', 'bonus' => '+5', 'danni' => '1d8+4'], ['nome' => 'Artigli', 'bonus' => '+5', 'danni' => '2d6+4']],
                'Olfatto acuto: vantaggio alle prove di Percezione basate sull\'olfatto.'],
            ['Scheletro', 13, 13, '9 m',
                [['nome' => 'Spada corta', 'bonus' => '+4', 'danni' => '1d6+2']],
                'Vulnerabilità: contundente. Immunità: veleno; condizione avvelenato.'],
        ];

        foreach ($mostri as [$nome, $hp, $ac, $velocita, $attacchi, $tratti]) {
            Monster::create([
                'name' => $nome,
                'hp' => $hp,
                'ac' => $ac,
                'speed' => $velocita,
                'attacks' => $attacchi,
                'traits' => $tratti,
                'created_by' => $autore?->getKey(),
            ]);
        }
    }

    private function news(?User $autore): void
    {
        Post::factory()->pinned()->create([
            'author_id' => $autore?->getKey(),
            'title' => 'La season 2 è cominciata',
            'slug' => 'season-2',
            'excerpt' => 'Due tavoli aperti, iscrizioni agli incarichi da stasera.',
            'cover_path' => Placeholder::make('news', 'La season 2 è cominciata'),
            'published_at' => now()->subDays(3),
        ]);

        foreach ([
            ['Nuove regole per il mercato', 'Gli annunci scadono dopo due settimane.', 6],
            ['Cercasi dungeon master', 'Chi vuole provare a condurre può farne richiesta dal proprio profilo.', 12],
            ['Il resoconto di Karrenmoor', 'La campagna dell\'inverno si è chiusa. Grazie a chi c\'era.', 20],
        ] as [$titolo, $sommario, $giorniFa]) {
            Post::factory()->create([
                'author_id' => $autore?->getKey(),
                'title' => $titolo,
                'slug' => \Illuminate\Support\Str::slug($titolo),
                'excerpt' => $sommario,
                'published_at' => now()->subDays($giorniFa),
            ]);
        }

        Post::factory()->scheduled()->create([
            'author_id' => $autore?->getKey(),
            'title' => 'Annuncio programmato',
            'slug' => 'annuncio-programmato',
        ]);
    }

    /** Il mercato: annunci attivi, uno già venduto, e due scambi. */
    private function mercato($personaggi): void
    {
        if ($personaggi->count() < 2) {
            return;
        }

        $primo = $personaggi->first();
        $secondo = $personaggi->get(1);

        MarketListing::factory()->soldBy($primo)->of('Pozione di Cura', 2, 90)->create([
            'details' => 'Due, prese in più al tempio.',
        ]);

        MarketListing::factory()->soldBy($secondo)->of('Corda di Seta', 1, 25)->create([
            'category' => 'Equipaggiamento',
            'unit_value' => 20,
        ]);

        MarketListing::factory()->soldBy($secondo)->of('Ascia Bipenne', 1, 40)->create([
            'category' => 'Armi',
            'unit_value' => 30,
            'status' => ListingStatus::Sold,
        ]);

        Trade::factory()->between($primo, $secondo)
            ->gold(give: 30)
            ->giving('Pergamena Bianca', 2, 5)
            ->wanting('Pozione di Cura', 1, 50)
            ->create(['message' => 'Mi servono per la prossima sessione.']);

        Trade::factory()->between($secondo, $primo)
            ->gold(want: 15)
            ->giving('Lanterna Cieca', 1, 10)
            ->create(['status' => TradeStatus::Accepted]);
    }

    /** Richieste: due in attesa, una approvata, una rifiutata. */
    private function richieste($personaggi, ?User $revisore): void
    {
        if ($personaggi->isEmpty()) {
            return;
        }

        $primo = $personaggi->first();
        $secondo = $personaggi->get(1) ?? $primo;

        PendingChange::factory()->forCharacter($primo)->create([
            'summary' => 'Ho riscritto la storia del personaggio.',
        ]);

        PendingChange::factory()->forCharacter($secondo)->loot(120)->create();

        if ($revisore) {
            PendingChange::factory()->forCharacter($primo)->levelUp(4)->approvedBy($revisore)->create();

            PendingChange::factory()->forCharacter($secondo)->loot(900)->rejectedBy($revisore)->create([
                'review_note' => 'Novecento monete da una sessione sola sono troppe: rifai il conto.',
            ]);
        }

        DmRequest::factory()->from($personaggi->last()->user)->create([
            'message' => 'Vorrei provare a condurre una one-shot per il gruppo.',
            'status' => PendingChangeStatus::Pending,
        ]);
    }
}
