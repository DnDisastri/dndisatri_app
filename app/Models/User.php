<?php

namespace App\Models;

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

#[Fillable(['name', 'email', 'password', 'avatar_path'])]
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
        ];
    }

    /**
     * Approvato da un amministratore, e quindi ammesso.
     *
     * Non è mass-assignable di proposito: si accende solo dal gesto esplicito di
     * approvazione (nel pannello o dalla riga di comando), mai da un form.
     */
    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /** I personaggi del giocatore, vivi e caduti. */
    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    /** Le campagne di cui è il dungeon master. */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'dm_id');
    }

    /** I richiami ricevuti, attivi e chiusi (D13). */
    public function warnings(): HasMany
    {
        return $this->hasMany(Warning::class);
    }

    /**
     * È sotto controllo adesso?
     *
     * Finché lo è, quattro azioni di mercato passano dall'approvazione di un DM
     * o di un admin: proporre uno scambio, accettarne uno, mettere in vendita,
     * comprare da un annuncio. Il negozio della gilda resta libero.
     */
    public function isUnderWarning(): bool
    {
        return $this->warnings()->active()->exists();
    }

    public function activeWarning(): ?Warning
    {
        return $this->warnings()->active()->latest()->first();
    }

    /**
     * Lo storico che DM e admin devono poter vedere: quante volte è stato
     * richiamato, e quanti giorni in tutto è stato sotto controllo.
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

    /**
     * Gli admin sono account di sola amministrazione: non hanno personaggi e
     * non compaiono davanti ai giocatori — né nella Gilda, né come autori di
     * un'approvazione. Chi ha deciso cosa si legge solo dal pannello.
     */
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

    /**
     * Il pannello di gestione è uno solo: quello che cambia è cosa ci si vede
     * dentro, deciso Resource per Resource. I giocatori non entrano.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() || $this->isDm();
    }
}
