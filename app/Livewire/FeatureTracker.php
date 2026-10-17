<?php

namespace App\Livewire;

use App\Actions\Characters\UseFeature;
use App\Domain\Dnd\Features;
use App\Models\Character;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use RuntimeException;

/**
 * Gli usi dei privilegi in cima al Turno: i contatori che tornano coi riposi,
 * i privilegi che consumano una riserva, e gli effetti accesi come l'Ira.
 */
class FeatureTracker extends Component
{
    /** L'id non deve essere manomettibile dal browser: qui vive il permesso. */
    #[Locked]
    public int $characterId;

    public function mount(Character $character): void
    {
        $this->characterId = $character->getKey();
    }

    public function usa(int $id): void
    {
        $this->fai(fn (Character $c) => app(UseFeature::class)->use($c, $id));
    }

    public function rimetti(int $id): void
    {
        $this->fai(fn (Character $c) => app(UseFeature::class)->restore($c, $id));
    }

    public function attiva(int $id): void
    {
        $this->fai(fn (Character $c) => app(UseFeature::class)->toggle($c, $id));
    }

    /** Il riposo lo prende l'intestazione: qui basta ridisegnarsi. */
    #[On('riposo-preso')]
    public function rinfresca(): void
    {
        //
    }

    private function fai(callable $azione): void
    {
        $character = Character::findOrFail($this->characterId);
        $this->authorize('manageSlots', $character);

        try {
            $azione($character);
            $this->resetErrorBag('usi');
        } catch (RuntimeException $e) {
            $this->addError('usi', $e->getMessage());
        }
    }

    public function render()
    {
        $character = Character::with('classes')->findOrFail($this->characterId);
        $privilegi = Features::conUsi($character);

        return view('livewire.feature-tracker', [
            'contatori' => $privilegi->filter(fn (array $p) => $p['massimo'] !== null || $p['attivabile']),
            // I privilegi che consumano una riserva stanno sotto la riserva.
            'spese' => $privilegi->filter(fn (array $p) => $p['consuma'] !== null)->groupBy('consuma'),
            'attivi' => $privilegi->filter(fn (array $p) => $p['attivo']),
            'puoGestire' => auth()->user()?->can('manageSlots', $character) ?? false,
        ]);
    }
}
