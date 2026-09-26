<?php

namespace App\Livewire;

use App\Actions\Characters\PrepareSpells;
use App\Models\Character;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

/**
 * Il libro degli incantesimi: quelli che si conoscono, e quelli pronti per oggi.
 *
 * La regola c'era già, scritta e provata (D16), e non la raggiungeva nessuna
 * pagina: `prepared` era una colonna che nessuno poteva cambiare. Chi prepara —
 * Chierico e Druido nei dati di gioco — sceglieva la lista del giorno da
 * nessuna parte.
 *
 * **Non passa dalla bacheca**, come gli slot e i punti ferita: preparare gli
 * incantesimi è quello che si fa al mattino nel gioco, non una modifica al
 * personaggio. Cambia ogni giorno per definizione.
 *
 * Chi non prepara vede lo stesso elenco senza caselle: quello che conosce è
 * sempre pronto, e una casella che non si può togliere sarebbe una bugia.
 */
class SpellBook extends Component
{
    /** L'id non deve essere manomettibile dal browser: qui vive il permesso. */
    #[Locked]
    public int $characterId;

    /** @var list<string> i nomi tenuti pronti per oggi */
    public array $preparati = [];

    public function mount(Character $character): void
    {
        $this->characterId = $character->getKey();
        $this->sincronizza($character);
    }

    /**
     * Si spunta e si sceglie: nessun pulsante «salva».
     *
     * Se il limite è superato l'azione rifiuta **tutto** l'elenco, e allora si
     * torna a quello vero: lasciare la casella spuntata direbbe che è andata,
     * e al prossimo giro si scoprirebbe di no.
     */
    public function updatedPreparati(): void
    {
        $character = $this->character();
        $this->authorize('managePreparedSpells', $character);

        try {
            app(PrepareSpells::class)->handle($character, array_values(array_filter($this->preparati)));
            $this->resetErrorBag('preparazione');
        } catch (RuntimeException $e) {
            $this->addError('preparazione', $e->getMessage());
            $this->sincronizza($this->character());
        }
    }

    private function sincronizza(Character $character): void
    {
        $this->preparati = $character->spells()
            ->where('level', '>', 0)
            ->where('prepared', true)
            ->pluck('name')
            ->all();
    }

    private function character(): Character
    {
        return Character::with('spells')->findOrFail($this->characterId);
    }

    public function render()
    {
        $character = $this->character();

        /*
         * I trucchetti stanno a parte perché non consumano slot e non si
         * preparano: non sono «incantesimi di livello zero», sono un'altra
         * cosa.
         */
        $gruppi = collect(['Trucchetti' => $character->spells->where('level', 0)])
            ->merge($character->spells
                ->where('level', '>', 0)
                ->sortBy('name')
                ->groupBy('level')
                ->mapWithKeys(fn ($spells, $level) => ['Livello '.$level => $spells]))
            ->filter(fn ($spells) => $spells->isNotEmpty());

        return view('livewire.spell-book', [
            'character' => $character,
            'gruppi' => $gruppi,
            'prepara' => $character->preparesSpells(),
            'limite' => $character->preparationLimit(),
            'canManage' => auth()->user()?->can('managePreparedSpells', $character) ?? false,
        ]);
    }
}
