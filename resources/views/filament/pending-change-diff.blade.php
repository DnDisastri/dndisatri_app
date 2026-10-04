@php
    // Stili inline: il CSS del pannello non compila le utility di questo Blade.
    $record = $getRecord();
    $character = $record->character;
    $rows = $record->diffRows();
    $fotoProposta = $record->proposedPhotoPath();
    $cambiaScheda = $rows->isNotEmpty() || $fotoProposta;
    $monete = $record->grantCoins();
    $haBottino = ! $monete->isEmpty() || ! empty($record->grant_items);
    $borsa = $character?->coins() ?? \App\Domain\Dnd\Coins::none();

    $etichetta = 'font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af;margin-bottom:.15rem;';
    $prima = 'color:#6b7280;text-decoration:line-through;word-break:break-word;';
    $dopo = 'color:#15803d;font-weight:600;word-break:break-word;';
    $uguale = 'color:#9ca3af;word-break:break-word;';
@endphp

<div style="font-size:.875rem;display:flex;flex-direction:column;gap:1.25rem;">
    @if ($record->isStale())
        <p style="border-radius:.375rem;background:#fef2f2;color:#b91c1c;padding:.5rem .75rem;">
            La scheda è stata modificata dopo questa proposta. Controlla la colonna
            <strong>Prima</strong>: quello che il giocatore vedeva poteva essere diverso.
        </p>
    @endif

    @if ($record->lacksBefore())
        <p style="border-radius:.375rem;background:#f3f4f6;color:#4b5563;padding:.5rem .75rem;">
            Questa richiesta è stata decisa prima che si salvasse com'era la scheda:
            qui si vede solo il <strong>Dopo</strong>.
        </p>
    @endif

    @if ($cambiaScheda)
        <div style="display:flex;flex-direction:column;gap:.9rem;">
            @foreach ($rows as $row)
                <div style="border-bottom:1px solid #e5e7eb;padding-bottom:.7rem;">
                    <div style="font-weight:600;margin-bottom:.35rem;{{ $row['changed'] ? '' : 'color:#9ca3af;' }}">{{ $row['label'] }}</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div>
                            <div style="{{ $etichetta }}">Prima</div>
                            <div style="{{ $row['changed'] ? $prima : $uguale }}">{{ $row['before'] ?? 'Non registrato' }}</div>
                        </div>
                        <div>
                            <div style="{{ $etichetta }}">Dopo</div>
                            <div style="{{ $row['changed'] ? $dopo : $uguale }}">{{ $row['after'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach

            @if ($fotoProposta)
                <div style="border-bottom:1px solid #e5e7eb;padding-bottom:.7rem;">
                    <div style="font-weight:600;margin-bottom:.35rem;">Foto</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div>
                            <div style="{{ $etichetta }}">Prima</div>
                            @if (! $record->isPending())
                                <div style="{{ $uguale }}">Non registrato</div>
                            @elseif ($character?->photoUrl())
                                <img src="{{ $character->photoUrl() }}" alt="Foto attuale"
                                    style="height:9rem;width:9rem;object-fit:cover;border-radius:.5rem;">
                            @else
                                <div style="{{ $prima }}text-decoration:none;">Nessuna foto</div>
                            @endif
                        </div>
                        <div>
                            <div style="{{ $etichetta }}">Dopo</div>
                            <img src="{{ route('pending-changes.photo', $record) }}" alt="Foto proposta"
                                style="height:9rem;width:9rem;object-fit:cover;border-radius:.5rem;outline:3px solid #22c55e;outline-offset:1px;">
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if ($haBottino)
        <div style="display:flex;flex-direction:column;gap:.5rem;">
            <div style="{{ $etichetta }}">Bottino</div>

            {{-- Decisa, la borsa attuale ha già il bottino dentro: si mostra solo cosa arriva. --}}
            @if (! $monete->isEmpty())
                <p>
                    <strong>Monete:</strong>
                    @if ($record->isPending())
                        {{ $borsa->format() }}
                        <span style="color:#9ca3af;">→</span>
                        <span style="{{ $dopo }}">{{ $borsa->plus($monete)->format() }}</span>
                        <span style="color:#6b7280;">(+{{ $monete->format() }})</span>
                    @else
                        <span style="{{ $dopo }}">+{{ $monete->format() }}</span>
                    @endif
                </p>
            @endif

            @if (! empty($record->grant_items))
                <div>
                    <div style="font-weight:600;margin-bottom:.25rem;">Oggetti</div>
                    <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.15rem;">
                        @foreach ($record->grant_items as $item)
                            @php
                                $extra = array_filter([
                                    filled($item['base'] ?? null) ? 'tipo: '.$item['base'] : null,
                                    ! empty($item['magic_bonus']) ? '+'.(int) $item['magic_bonus'] : null,
                                    $item['category'] ?? null,
                                    ! empty($item['value_cp']) ? \App\Domain\Dnd\Coins::formatValue((int) $item['value_cp']) : null,
                                ]);
                            @endphp
                            <li style="color:#15803d;font-weight:600;">
                                + {{ $item['qty'] ?? 1 }}× {{ $item['name'] }}
                                @if ($extra)
                                    <span style="color:#6b7280;font-weight:400;">({{ implode(' · ', $extra) }})</span>
                                @endif
                                @if (filled($item['details'] ?? null))
                                    <div style="color:#6b7280;font-weight:400;margin-left:1rem;word-break:break-word;">{{ $item['details'] }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    @unless ($cambiaScheda || $haBottino)
        <p style="color:#6b7280;">Questa richiesta non modifica direttamente la scheda.</p>
    @endunless
</div>
