<?php

namespace App\Models;

use App\Enums\NotificationCategory;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password', 'avatar_path', 'discovery_source', 'played_before'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_DM = 'dm';

    public const ROLE_PLAYER = 'player';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'password' => 'hashed',
            'muted_notifications' => 'array',
            'played_before' => 'boolean',
        ];
    }

    /** Si salvano le categorie disattivate: una nuova parte attiva per tutti. */
    public function wantsEmailFor(NotificationCategory $categoria): bool
    {
        return ! in_array($categoria->value, $this->muted_notifications ?? [], true);
    }

    /** Approvato da un admin. Non mass-assignable: si accende solo dal gesto esplicito, mai da un form. */
    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /** Null per chi si è iscritto prima che si facesse la domanda. */
    public static function playedBeforeLabel(?bool $playedBefore): string
    {
        return match ($playedBefore) {
            true => 'Sì',
            false => 'No',
            null => 'Non indicato',
        };
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    /** I posti chiesti alle sessioni, ritirati compresi. */
    public function sessionBookings(): HasMany
    {
        return $this->hasMany(SessionBooking::class);
    }

    /** Le campagne di cui è il dungeon master. */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'dm_id');
    }

    /** Attivi e chiusi: un richiamo revocato resta nello storico. */
    public function warnings(): HasMany
    {
        return $this->hasMany(Warning::class);
    }

    /** Sotto controllo: quattro azioni di mercato (scambi, annunci, acquisti dagli annunci) passano dall'approvazione. Il negozio resta libero. */
    public function isUnderWarning(): bool
    {
        return $this->warnings()->active()->exists();
    }

    public function activeWarning(): ?Warning
    {
        return $this->warnings()->active()->latest()->first();
    }

    /**
     * Lo storico per DM e admin: quante volte richiamato e quanti giorni in tutto.
     *
     * @return array{count: int, days: int}
     */
    public function warningHistory(): array
    {
        $warnings = $this->warnings()->get();

        return [
            'count' => $warnings->count(),
            'days' => (int) $warnings->sum(fn (Warning $w) => $w->daysLasted()),
        ];
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /** Gli admin sono di sola amministrazione: non hanno personaggi e non compaiono davanti ai giocatori. */
    public function scopeVisibleToPlayers(Builder $query): void
    {
        $query->whereDoesntHave(
            'roles',
            fn (Builder $roles) => $roles->where('name', self::ROLE_ADMIN)
        );
    }

    public function isDm(): bool
    {
        return $this->hasRole(self::ROLE_DM);
    }

    /** Al pannello entrano DM e admin; cosa ci vedono è deciso Resource per Resource. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() || $this->isDm();
    }
}
