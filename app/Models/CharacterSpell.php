<?php

namespace App\Models;

use App\Domain\Dnd\SpellName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['character_id', 'name', 'level', 'description', 'prepared'])]
class CharacterSpell extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['prepared' => 'boolean'];
    }


    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function isCantrip(): bool
    {
        return $this->level === 0;
    }

    public function scopeCantrips(Builder $query): void
    {
        $query->where('level', 0);
    }

    public function scopeAtLevel(Builder $query, int $level): void
    {
        $query->where('level', $level);
    }

    /**
     * La descrizione scritta sulla scheda, o in mancanza quella della libreria
     * interna. Il nome si confronta normalizzato, così «palla di fuoco» trova
     * comunque «Palla di Fuoco».
     */
    public function descriptionOrDefault(): ?string
    {
        return $this->description ?: SpellName::description($this->name);
    }

    /**
     * Come si tira questo incantesimo: `'attacco'`, `'cd'`, o niente.
     *
     * Si deduce dalla descrizione, perché non abbiamo una colonna che lo dica e
     * aggiungerla vorrebbe dire ricompilare a mano trecento incantesimi. Le
     * descrizioni della libreria sono scritte tutte uguali — «Tiro per colpire
     * a distanza», «TS su Destrezza» — e tanto basta.
     *
     * Serve alla sezione Turno, dove ogni riga deve dire il **suo** numero: là
     * non si legge «il tuo attacco con incantesimo è +7» in cima e poi ci si
     * arrangia, si legge «Dardo di Fuoco, +7 per colpire».
     *
     * È una deduzione, quindi sbaglia in silenzio verso il basso: se non
     * riconosce niente non scrive niente, invece di scrivere il numero
     * sbagliato.
     */
    public function rollKind(): ?string
    {
        $testo = Str::lower(Str::ascii((string) $this->descriptionOrDefault()));

        return match (true) {
            str_contains($testo, 'colpire') || str_contains($testo, 'attacco a distanza')
                || str_contains($testo, 'attacco in mischia') => 'attacco',
            str_contains($testo, 'ts su') || str_contains($testo, 'tiro salvezza') => 'cd',
            default => null,
        };
    }

    /*
     * Le colonne del cheat sheet (Turno), dedotte dalla descrizione come
     * rollKind. Le sintesi della libreria hanno una grammatica fissa —
     * «<Scuola>, liv. N. <Tempo>, <gittata>. <effetto; danni>» — e tanto basta a
     * pescarne i pezzi. È una deduzione: quando non riconosce, non scrive
     * (sbaglia in silenzio verso il basso), e il dettaglio per esteso resta in
     * «Magia». Per un incantesimo scritto a mano dal giocatore, fuori grammatica,
     * le colonne restano un trattino — mai un dato sbagliato.
     */

    /**
     * Il «cappello» della descrizione: scuola, livello, tempo e gittata, cioè
     * tutto quello che viene **prima** dell'effetto. Si taglia al primo segno
     * d'effetto (il tiro, i danni, il «;»), così un «spinta di 3 m» o un «entro
     * 9 m» dentro l'effetto non si spaccia per gittata — e il «liv. 1.» col suo
     * punto non serve più a delimitare niente.
     */
    private function testata(): string
    {
        $desc = (string) $this->descriptionOrDefault();

        return preg_split(
            '/\b(?:TS su|Tiro per colpire|Attacco (?:a distanza|in mischia))\b|\bdanni\b|;/iu',
            $desc, 2
        )[0];
    }

    /** Tempo di lancio: azione, azione bonus, reazione, o una durata. */
    public function castingTime(): string
    {
        $t = Str::lower(Str::ascii($this->testata()));

        return match (true) {
            str_contains($t, 'azione bonus') => 'Azione bonus',
            str_contains($t, 'reazione') => 'Reazione',
            (bool) preg_match('/(\d+)\s*minut/', $t, $m) => $m[1].' min',
            str_contains($t, 'or') && preg_match('/(\d+)\s*or[ae]/', $t, $m) => $m[1].($m[1] === '1' ? ' ora' : ' ore'),
            // Quasi tutto è un'azione: è il caso comune, e per un cheat sheet di
            // combattimento è la scommessa giusta quando il testo non dice altro.
            default => 'Azione',
        };
    }

    /** Gittata: metri, tocco, personale, cono/cubo — presa dalla frase giusta,
     *  così un «spinta di 3 m» dentro l'effetto non passa per gittata. */
    public function range(): ?string
    {
        $t = Str::lower(Str::ascii($this->testata()));

        return match (true) {
            (bool) preg_match('/gittata\s+([\d.,]+)\s*m/', $t, $m) => $m[1].' m',
            str_contains($t, 'tocco') => 'Tocco',
            str_contains($t, 'personale') => 'Personale',
            (bool) preg_match('/con[oi] di\s+([\d.,]+)\s*m/', $t, $m) => 'Cono '.$m[1].' m',
            (bool) preg_match('/cub[oi] di\s+([\d.,]+)\s*m/', $t, $m) => 'Cubo '.$m[1].' m',
            (bool) preg_match('/([\d.,]+)\s*m\b/', $t, $m) => $m[1].' m',
            default => null,
        };
    }

    /** La caratteristica del tiro salvezza, in sigla: «TS su Destrezza» → DES. */
    public function saveAbility(): ?string
    {
        $t = Str::lower(Str::ascii((string) $this->descriptionOrDefault()));

        if (! preg_match('/ts su (\w+)/', $t, $m)) {
            return null;
        }

        return match ($m[1]) {
            'forza' => 'FOR', 'destrezza' => 'DES', 'costituzione' => 'COS',
            'intelligenza' => 'INT', 'saggezza' => 'SAG', 'carisma' => 'CAR',
            default => null,
        };
    }

    /**
     * I danni: dado base + tipo. Un trucchetto scala col livello del
     * personaggio («1d10 … (2d10 al 5°, 3d10 all'11°…)»), e se lo si passa si
     * sceglie la fascia giusta.
     */
    public function damage(?int $characterLevel = null): ?string
    {
        $desc = (string) $this->descriptionOrDefault();

        if (! preg_match('/(\d+d\d+(?:\s*\+\s*\d+)?)/', $desc, $m)) {
            return null;
        }

        $dado = preg_replace('/\s+/', '', $m[1]);

        if ($this->isCantrip() && $characterLevel !== null) {
            preg_match_all('/(\d+d\d+)\s+al[l\']*\s*(\d+)/u', $desc, $fasce, PREG_SET_ORDER);
            foreach ($fasce as $fascia) {
                if ($characterLevel >= (int) $fascia[2]) {
                    $dado = $fascia[1];
                }
            }
        }

        $tipo = preg_match('/danni?\s+(?:da\s+)?([a-zàèéìòù]+)/iu', $desc, $mt) ? Str::lower($mt[1]) : null;

        return $tipo ? $dado.' '.$tipo : $dado;
    }

    /** La spia ↑: l'incantesimo rende di più lanciato in uno slot superiore. */
    public function scalesUp(): bool
    {
        $t = Str::lower(Str::ascii((string) $this->descriptionOrDefault()));

        return str_contains($t, 'livelli superiori')
            || str_contains($t, 'slot superiore')
            || str_contains($t, 'per slot');
    }
}
