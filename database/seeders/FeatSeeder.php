<?php

namespace Database\Seeders;

use App\Enums\RulesEdition;
use App\Models\Feat;
use Illuminate\Database\Seeder;

/**
 * I talenti del Manuale del Giocatore 2014, con un riassunto breve scritto
 * da noi: il testo completo è sul manuale. Si correggono dal pannello.
 */
class FeatSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::catalogo() as $talento) {
            Feat::updateOrCreate(
                ['name' => $talento['name'], 'rules_edition' => RulesEdition::Classic->value],
                [
                    'prerequisite' => $talento['prerequisite'] ?? null,
                    'description' => $talento['description'],
                    'is_homebrew' => false,
                ],
            );
        }
    }

    /** @return list<array{name: string, prerequisite?: string, description: string}> */
    private static function catalogo(): array
    {
        return [
            ['name' => 'Abile', 'description' => 'Competenza in tre abilità o strumenti a scelta, in qualsiasi combinazione.'],
            ['name' => 'Addestrato nelle Armature Leggere', 'description' => '+1 a Forza o Destrezza. Competenza nelle armature leggere.'],
            ['name' => 'Addestrato nelle Armature Medie', 'prerequisite' => 'Competenza nelle armature leggere', 'description' => '+1 a Forza o Destrezza. Competenza nelle armature medie e negli scudi.'],
            ['name' => 'Addestrato nelle Armature Pesanti', 'prerequisite' => 'Competenza nelle armature medie', 'description' => '+1 a Forza. Competenza nelle armature pesanti.'],
            ['name' => 'Adepto Elementale', 'prerequisite' => 'Saper lanciare almeno un incantesimo', 'description' => 'Scegli un tipo fra acido, freddo, fuoco, fulmine e tuono: i tuoi incantesimi ignorano la resistenza a quel danno, e sui dadi di danno gli 1 contano come 2. Si può prendere più volte, con un tipo diverso.'],
            ['name' => 'Adepto Marziale', 'description' => 'Impari due manovre del Maestro di Battaglia e ottieni un dado di superiorità (d6), che torna con un riposo breve o lungo.'],
            ['name' => 'Allerta', 'description' => "+5 all'iniziativa. Non puoi essere sorpreso mentre sei cosciente, e chi non vedi non ha vantaggio per attaccarti."],
            ['name' => 'Atleta', 'description' => '+1 a Forza o Destrezza. Rialzarsi da prono costa solo 1,5 m di movimento, arrampicarsi non rallenta, e salti con soli 1,5 m di rincorsa.'],
            ['name' => 'Attaccante Selvaggio', 'description' => "Una volta per turno, quando tiri i danni di un attacco con un'arma da mischia, puoi ritirarli e tenere il risultato che preferisci."],
            ['name' => 'Attore', 'description' => '+1 a Carisma. Vantaggio a Inganno e Intrattenere quando ti spacci per qualcun altro, e puoi imitare la voce di una persona o il verso di una creatura che hai sentito.'],
            ['name' => 'Carica', 'description' => "Dopo aver usato Scatto, con un'azione bonus puoi attaccare in mischia o spingere: se ti sei mosso di almeno 3 m in linea retta, +5 ai danni oppure spingi il bersaglio di 3 m."],
            ['name' => 'Cecchino Magico', 'prerequisite' => 'Saper lanciare almeno un incantesimo', 'description' => 'Gli incantesimi con tiro per colpire raddoppiano la gittata e ignorano la copertura parziale e tre quarti. Impari un trucchetto con tiro per colpire.'],
            ['name' => 'Combattente con Due Armi', 'description' => "+1 alla CA mentre impugni un'arma in ciascuna mano. Puoi combattere con due armi anche se non sono leggere, ed estrarre o riporre due armi insieme."],
            ['name' => 'Combattente in Sella', 'description' => 'Vantaggio in mischia contro le creature non in sella più piccole della tua cavalcatura. Puoi deviare su di te gli attacchi rivolti a lei, che subisce meno danni dagli effetti con tiro salvezza su Destrezza.'],
            ['name' => 'Duellante Difensivo', 'prerequisite' => 'Destrezza 13 o superiore', 'description' => "Quando ti colpiscono in mischia mentre impugni un'arma accurata in cui sei competente, con la reazione aggiungi la competenza alla CA per quell'attacco."],
            ['name' => 'Esperto di Balestre', 'description' => "Ignori la proprietà ricarica delle balestre, non hai svantaggio a tirare con una creatura accanto, e dopo un attacco con un'arma a una mano puoi tirare con una balestra a mano come azione bonus."],
            ['name' => 'Esploratore di Dungeon', 'description' => 'Vantaggio alle prove per trovare porte segrete e ai tiri salvezza contro le trappole, resistenza ai loro danni, e cerchi trappole anche a passo normale.'],
            ['name' => 'Fortunato', 'description' => 'Tre punti fortuna per riposo lungo: spendine uno per tirare un d20 in più su un tuo tiro (o su un attacco contro di te) e scegliere quale dado usare.'],
            ['name' => 'Furtivo', 'prerequisite' => 'Destrezza 13 o superiore', 'description' => 'Puoi nasconderti anche in leggera oscurità. Mancare un attacco a distanza non rivela dove sei, e la penombra non ti dà svantaggio a Percezione.'],
            ['name' => 'Guaritore', 'description' => "Stabilizzare con un kit da guaritore riporta a 1 PF. Con un'azione e un uso del kit fai recuperare 1d6 + 4 + i dadi vita massimi del bersaglio, una volta per riposo per creatura."],
            ['name' => 'Incantatore da Guerra', 'prerequisite' => 'Saper lanciare almeno un incantesimo', 'description' => 'Vantaggio ai tiri salvezza di Costituzione per la concentrazione, puoi lanciare con le mani occupate da armi o scudo, e come attacco di opportunità puoi lanciare un incantesimo.'],
            ['name' => 'Incantatore Rituale', 'prerequisite' => 'Intelligenza o Saggezza 13 o superiore', 'description' => 'Un libro con due incantesimi rituali di 1° di una classe a scelta, che lanci solo come rituali. Ne puoi copiare altri trovati in giro.'],
            ['name' => 'Iniziato alla Magia', 'description' => 'Scegli una classe da incantatore: impari due suoi trucchetti e un incantesimo di 1°, che lanci una volta per riposo lungo.'],
            ['name' => 'Leader Ispiratore', 'prerequisite' => 'Carisma 13 o superiore', 'description' => 'Con 10 minuti di discorso, fino a sei creature guadagnano PF temporanei pari al tuo livello più il modificatore di Carisma. Una volta per riposo per creatura.'],
            ['name' => 'Linguista', 'description' => '+1 a Intelligenza. Impari tre lingue e sai creare cifrari che solo chi conosci (o chi supera una prova difficile) riesce a leggere.'],
            ['name' => 'Lottatore', 'prerequisite' => 'Forza 13 o superiore', 'description' => "Vantaggio ai tiri per colpire contro la creatura che stai afferrando. Con un'azione puoi provare a immobilizzarla: se vinci un'altra prova di lotta, tu e lei siete trattenuti finché la presa non finisce."],
            ['name' => "Maestro d'Armi", 'description' => '+1 a Forza o Destrezza. Competenza in quattro armi a scelta.'],
            ['name' => 'Maestro degli Scudi', 'description' => "Dopo l'azione di attacco puoi spingere con lo scudo come azione bonus. Aggiungi la CA dello scudo ai tiri salvezza su Destrezza contro un solo bersaglio, e se lo superi non subisci danni."],
            ['name' => 'Maestro delle Armature Medie', 'prerequisite' => 'Competenza nelle armature medie', 'description' => 'Le armature medie non ti danno svantaggio a Furtività, e con Destrezza 16 o più aggiungi fino a +3 alla CA invece di +2.'],
            ['name' => 'Maestro delle Armature Pesanti', 'prerequisite' => 'Competenza nelle armature pesanti', 'description' => "+1 a Forza. Con un'armatura pesante, i danni contundenti, perforanti e taglienti da armi non magiche si riducono di 3."],
            ['name' => 'Maestro delle Armi ad Asta', 'description' => 'Con alabarda, falcione, bastone o lancia puoi attaccare con il manico come azione bonus (d4). Chi entra nella tua portata provoca un attacco di opportunità.'],
            ['name' => 'Maestro delle Armi Possenti', 'description' => "Dopo un critico o un nemico a 0 PF fai un attacco in mischia come azione bonus. Con un'arma pesante puoi prendere -5 al tiro per colpire per +10 ai danni."],
            ['name' => 'Mente Acuta', 'description' => "+1 a Intelligenza. Sai sempre dov'è il nord e quante ore mancano all'alba o al tramonto, e ricordi tutto quello che hai visto o sentito nell'ultimo mese."],
            ['name' => 'Mobile', 'description' => '+3 m di velocità. Lo Scatto ignora il terreno difficile, e chi attacchi in mischia non può farti attacchi di opportunità per quel turno.'],
            ['name' => 'Osservatore', 'description' => '+1 a Intelligenza o Saggezza. Leggi le labbra nelle lingue che conosci e hai +5 a Percezione e Indagare passive.'],
            ['name' => 'Resiliente', 'description' => '+1 a una caratteristica a scelta e competenza nei tiri salvezza di quella caratteristica.'],
            ['name' => 'Resistente', 'description' => '+1 a Costituzione. Quando tiri un dado vita per recuperare PF, recuperi almeno il doppio del tuo modificatore di Costituzione (minimo 2).'],
            ['name' => 'Rissaiolo', 'description' => "+1 a Forza o Costituzione. Competenza nelle armi improvvisate, colpo senz'armi da d4, e dopo averlo colpito puoi provare ad afferrare il bersaglio come azione bonus."],
            ['name' => 'Robusto', 'description' => 'I tuoi punti ferita massimi aumentano di 2 per ogni livello, compresi quelli che prenderai.'],
            ['name' => 'Sentinella', 'description' => 'Chi colpisci con un attacco di opportunità si ferma lì. Lo fai anche se usa Disimpegno, e puoi attaccare chi accanto a te prende di mira un alleato.'],
            ['name' => 'Tiratore Scelto', 'description' => "Nessuno svantaggio a gittata lunga e ignori la copertura parziale e tre quarti. Con un'arma a distanza puoi prendere -5 al tiro per colpire per +10 ai danni."],
            ['name' => 'Uccisore di Maghi', 'description' => 'Puoi attaccare con la reazione chi lancia un incantesimo accanto a te. Chi colpisci ha svantaggio alla concentrazione, e hai vantaggio ai tiri salvezza contro gli incantesimi lanciati da vicino.'],
        ];
    }
}
