<?php

namespace App\Models;

use App\Domain\Dnd\Ability;
use App\Domain\Dnd\Checks;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['character_id', 'name', 'attack_ability', 'weapon_bonus', 'damage'])]
class CharacterWeapon extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'attack_ability' => Ability::class,
            'weapon_bonus' => 'integer',
        ];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * Bonus al tiro per colpire. Va passato il personaggio perché il calcolo
     * usa i punteggi EFFICACI, cioè con gli oggetti magici già applicati.
     */
    public function attackBonus(Character $character): int
    {
        return Checks::weaponAttack(
            $character->effectiveScores(),
            $this->attack_ability,
            $character->proficiencyBonus(),
            $this->weapon_bonus,
        );
    }
}
