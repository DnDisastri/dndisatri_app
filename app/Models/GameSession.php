<?php

namespace App\Models;

use App\Enums\SeatStatus;
use App\Enums\SpecialSessionType;
use App\Models\Concerns\HasReactions;
use App\Models\Scopes\HideDrafts;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as BaseCollection;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Una sessione di gioco: posti, prenotazioni, quest, presenze e resoconto.
 * Non `Session`, per non confondersi con le sessioni di login di Laravel.
 */
#[Fillable(['campaign_id', 'number', 'title', 'season', 'special_type', 'played_at', 'published_at', 'created_by', 'min_players', 'max_players'])]
class GameSession extends Model
{
    use HasFactory, HasReactions, LogsActivity;

    /** @var array<int, SessionBooking|null> posti già letti dagli elenchi, per utente */
    private array $postiNoti = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('sessione')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'played_at' => 'datetime',
            'recap_written_at' => 'datetime',
            'players_notified_at' => 'datetime',
            'published_at' => 'datetime',
            'rewards' => 'array',
            'min_players' => 'integer',
            'max_players' => 'integer',
            'season' => 'integer',
            'special_type' => SpecialSessionType::class,
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Solo storico: i combattimenti vivono anche senza sessione. */
    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
    }

    /** Le quest che il DM ha messo in questa sessione. */
    public function quests(): HasMany
    {
        return $this->hasMany(Quest::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new HideDrafts);
    }

    /** Non ancora pubblicata: la vedono solo DM e admin. */
    public function isDraft(): bool
    {
        return $this->published_at === null;
    }

    /** I tavoli di una sessione speciale; le sessioni normali non ne hanno. */
    public function tables(): HasMany
    {
        return $this->hasMany(SessionTable::class)->orderBy('position')->orderBy('id');
    }

    public function hasTables(): bool
    {
        return $this->relationLoaded('tables') ? $this->tables->isNotEmpty() : $this->tables()->exists();
    }

    /** Con i tavoli, i posti della sessione sono la loro somma. */
    public function syncSeatsFromTables(): void
    {
        $posti = (int) $this->tables()->sum('seats');

        if ($posti > 0 && $posti !== $this->max_players) {
            $this->forceFill(['max_players' => $posti, 'min_players' => min($this->min_players, $posti)])->saveQuietly();
        }
    }

    /** Tutti i posti, ospiti compresi. */
    public function bookings(): HasMany
    {
        return $this->hasMany(SessionBooking::class);
    }

    /** Le prenotazioni dei giocatori registrati, ritirati compresi. */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'game_session_bookings')
            ->withPivot(['character_id', 'status', 'joined_at', 'decided_at'])
            ->withTimestamps();
    }

    /** I personaggi dei posti confermati, per gli eroi del DM e il combattimento: un'offerta non è ancora un sì. */
    public function bookedCharacters(): Collection
    {
        $ids = $this->bookings()->confirmed()->pluck('character_id')->filter();

        return Character::query()
            ->alive()
            ->whereIn('id', $ids)
            ->with(['user', 'items', 'itemEffects'])
            ->orderBy('name')
            ->get();
    }

    /** Chi la conduce oltre al titolare della campagna: co-master, sostituti, DM dei tavoli. */
    public function dms(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'game_session_dm')->withTimestamps();
    }

    /** Segnato sulla sessione o su uno dei suoi tavoli. */
    public function isRunBy(User $user): bool
    {
        return $this->conductorIds()->contains($user->getKey());
    }

    /** Il titolare, i DM della sessione e quelli dei tavoli, senza doppioni. */
    public function conductors(): BaseCollection
    {
        $this->loadMissing(['campaign.dm', 'dms', 'tables.dms']);

        return collect([$this->campaign?->dm])->filter()
            ->concat($this->dms)
            ->concat($this->tables->flatMap->dms)
            ->unique('id')
            ->values();
    }

    /** @return BaseCollection<int, int> */
    private function conductorIds(): BaseCollection
    {
        // Policy e viste lo chiedono più volte per pagina: si carica una volta sola.
        // Negli elenchi va caricato con `with(['dms', 'tables.dms'])`.
        $this->loadMissing(['dms', 'tables.dms']);

        return collect($this->dms->modelKeys())->concat($this->tables->flatMap(fn (SessionTable $t) => $t->dms->modelKeys()));
    }

    public function recapWrittenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recap_written_by');
    }

    /** Chi c'era davvero, non chi si era iscritto. Il pivot porta anche il personaggio (nullo per chi conduce). */
    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['character_id', 'session_table_id'])
            ->withTimestamps();
    }

    /** I personaggi che hanno giocato: dal pivot, non dai giocatori (conta «Grimm c'era», non «Marco»). */
    public function playedCharacters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class, 'game_session_user');
    }

    // === Stato ===

    public function isUpcoming(): bool
    {
        return $this->played_at->isFuture();
    }

    /**
     * Tipo e stagione valgono solo nella campagna speciale: spostando una sessione
     * in una campagna normale si azzerano.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function withoutSpecialFieldsUnlessSpecial(array $data): array
    {
        if (! Campaign::query()->whereKey($data['campaign_id'] ?? null)->value('is_special')) {
            $data['special_type'] = null;
            $data['season'] = null;
        }

        return $data;
    }

    /** Sta nella campagna delle sessioni speciali. */
    public function isSpecial(): bool
    {
        return (bool) $this->campaign?->isSpecial();
    }

    /** Circa una pagina. */
    public const RECAP_MAX = 3600;

    /** Quelli scritti prima del limite si accorciano, ma non si allungano. */
    public static function recapLimit(?string $attuale): int
    {
        return max(self::RECAP_MAX, mb_strlen((string) $attuale));
    }

    public function hasRecap(): bool
    {
        return filled($this->recap);
    }

    // === Posti ===

    /** Ci si prenota fino all'inizio della sessione. */
    public function acceptsBookings(): bool
    {
        return $this->isUpcoming() && ! $this->isDraft();
    }

    /** Offerti e confermati, ospiti compresi: il posto è tenuto per loro. */
    public function participantCount(): int
    {
        return $this->bookings()->holdingSeat()->count();
    }

    public function confirmedCount(): int
    {
        // Negli elenchi arriva da `withCount`, senza una query per riga.
        return $this->confirmed_bookings_count ?? $this->bookings()->confirmed()->count();
    }

    public function freeSlots(): int
    {
        return max(0, $this->max_players - $this->participantCount());
    }

    /** Non ci sono posti da offrire: si può ancora chiedere, si finisce fra le richieste. */
    public function isFull(): bool
    {
        return $this->freeSlots() === 0;
    }

    /** Tutti i posti confermati: è il momento di chiedere agli altri se restano come riserve. */
    public function isFullyConfirmed(): bool
    {
        return $this->confirmedCount() >= $this->max_players;
    }

    /** Il minimo è un'indicazione per il DM, non un divieto. */
    public function missingToMinimum(): int
    {
        return max(0, $this->min_players - $this->confirmedCount());
    }

    public function hasMinimum(): bool
    {
        return $this->missingToMinimum() === 0;
    }

    public function bookingOf(User $user): ?SessionBooking
    {
        if (array_key_exists($user->getKey(), $this->postiNoti)) {
            return $this->postiNoti[$user->getKey()];
        }

        return $this->bookings()->where('user_id', $user->getKey())->first();
    }

    /**
     * Gli elenchi leggono in una volta i posti dell'utente su tutte le sessioni
     * e li consegnano qui: `bookingOf` e i permessi non rifanno la query per riga.
     *
     * @param  Collection<int, GameSession>  $sessions
     */
    public static function rememberBookingsOf(User $user, Collection $sessions): void
    {
        $posti = $user->sessionBookings()->whereIn('game_session_id', $sessions->modelKeys())->get()->keyBy('game_session_id');

        $sessions->each(fn (GameSession $s) => $s->postiNoti[$user->getKey()] = $posti->get($s->getKey()));
    }

    public function seatOf(User $user): ?SeatStatus
    {
        return $this->bookingOf($user)?->status;
    }

    /** Il personaggio con cui il giocatore si è prenotato. */
    public function bookedCharacterOf(User $user): ?int
    {
        return $this->players()->whereKey($user->getKey())->first()?->pivot?->character_id;
    }

    /** Ha chiesto un posto o ce l'ha: chi si è ritirato non conta. */
    public function hasParticipant(User $user): bool
    {
        return $this->seatOf($user)?->isActive() ?? false;
    }

    /** Un DM che non è quello della campagna: gli admin non sostituiscono, amministrano. */
    public function isSubstitute(User $user): bool
    {
        $titolare = $this->campaign?->dm_id;

        return $titolare !== null && ! $user->isAdmin() && $user->isDm() && $user->getKey() !== $titolare
            && ! $this->isRunBy($user);
    }

    /** Per la firma «in sostituzione di». */
    public function recapBySubstitute(): bool
    {
        $titolare = $this->campaign?->dm_id;

        return $titolare !== null
            && $this->recap_written_by !== null
            && $this->recap_written_by !== $titolare
            && ! ($this->recapWrittenBy?->isAdmin() ?? false);
    }

    public function attended(User $user): bool
    {
        return $this->relationLoaded('attendees')
            ? $this->attendees->contains($user)
            : $this->attendees()->whereKey($user->getKey())->exists();
    }

    /** Solo la parte "Sessione 12", senza il titolo: serve dove le due righe si impilano. */
    public function numberLabel(): string
    {
        if ($this->special_type !== null) {
            return $this->special_type->label().($this->season ? " · Season {$this->season}" : '');
        }

        return $this->number !== null ? "Sessione {$this->number}" : 'Sessione';
    }

    /** Titolo da mostrare: "Sessione 12: La Torre Nera", o quel che c'è. */
    public function displayTitle(): string
    {
        // Le speciali non hanno numero: «Apertura di stagione: Il risveglio».
        if ($this->special_type !== null) {
            return filled($this->title) ? "{$this->special_type->label()}: {$this->title}" : $this->special_type->label();
        }

        return filled($this->title) ? "{$this->numberLabel()}: {$this->title}" : $this->numberLabel();
    }

    // === Query ===

    /** Le prossime, dalla più vicina. */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('played_at', '>=', now())->orderBy('played_at');
    }

    /** Lo storico, dalla più recente. */
    public function scopePast(Builder $query): void
    {
        $query->where('played_at', '<', now())->orderByDesc('played_at');
    }

    public function scopeWithRecap(Builder $query): void
    {
        $query->whereNotNull('recap')->where('recap', '!=', '');
    }

    /** Si reagisce al resoconto, quindi solo quando c'è. */
    public function acceptsReactions(): bool
    {
        return $this->hasRecap();
    }
}
