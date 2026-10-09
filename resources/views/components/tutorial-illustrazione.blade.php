@props(['quale'])

@php
    use App\Enums\Icon;
    use App\Enums\TutorialIllustration;
@endphp

{{-- Disegno fisso di un passo, scelto dal campo `illustration`. --}}
@switch($quale)
    @case(TutorialIllustration::BottomBar)
        <div class="rounded-card bg-primary p-3">
            <div class="flex items-center justify-between gap-2">
                @foreach ([Icon::Campaigns, Icon::Ledger] as $icona)
                    <span class="flex h-10 w-10 items-center justify-center rounded-full text-on-primary-soft">
                        <x-icona :is="$icona" class="h-6 w-6" />
                    </span>
                @endforeach
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-active text-on-active shadow-lg">
                    <x-icona :is="Icon::Characters" class="h-7 w-7" />
                </span>
                @foreach ([Icon::Market, Icon::Events] as $icona)
                    <span class="flex h-10 w-10 items-center justify-center rounded-full text-on-primary-soft">
                        <x-icona :is="$icona" class="h-6 w-6" />
                    </span>
                @endforeach
            </div>
        </div>
        @break

    @case(TutorialIllustration::Menu)
        <div class="flex items-center justify-end gap-2 rounded-card bg-primary p-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-page text-fg">
                <x-icona :is="Icon::Notifications" class="h-5 w-5" />
            </span>
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-active text-on-active">
                <x-icona :is="Icon::Menu" class="h-6 w-6" />
            </span>
        </div>
        @break

    @case(TutorialIllustration::Hero)
        <div class="flex items-center justify-center gap-4 rounded-card bg-primary p-5">
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-active text-on-active shadow-lg">
                <x-icona :is="Icon::Characters" class="h-8 w-8" />
            </span>
            <x-icona :is="Icon::GoTo" class="h-6 w-6 text-on-primary-soft" />
            <div class="rounded-card border border-white/20 bg-white/10 px-4 py-3">
                <div class="h-2 w-16 rounded-full bg-white/50"></div>
                <div class="mt-2 h-2 w-10 rounded-full bg-white/30"></div>
                <div class="mt-2 h-2 w-14 rounded-full bg-white/30"></div>
            </div>
        </div>
        @break

    @case(TutorialIllustration::Sheet)
        <div class="rounded-card bg-primary p-3">
            <div class="flex flex-wrap items-center gap-1.5 text-xs font-semibold">
                <span class="rounded-full bg-active px-3 py-1.5 text-on-active">Turno</span>
                @foreach (['Prove', 'Magia', 'Zaino', 'Storia'] as $tab)
                    <span class="rounded-full px-3 py-1.5 text-on-primary-soft">{{ $tab }}</span>
                @endforeach
            </div>
            <div class="mt-3 space-y-2">
                <div class="h-2 w-3/4 rounded-full bg-white/30"></div>
                <div class="h-2 w-1/2 rounded-full bg-white/20"></div>
            </div>
        </div>
        @break

    @case(TutorialIllustration::Quest)
        <div class="rounded-card bg-primary p-3">
            <div class="flex items-center justify-between gap-2">
                <div class="h-3 w-28 rounded-full bg-white/30"></div>
                <span class="rounded-full bg-white/15 px-2 py-0.5 text-xs font-semibold text-on-primary">Media</span>
            </div>
            <p class="mt-3 text-xs text-on-primary-soft">Interessa a 3 giocatori</p>
            <span class="mt-3 inline-flex items-center justify-center rounded-full bg-active px-4 py-1.5 text-sm font-semibold text-on-active">
                Mi interessa
            </span>
        </div>
        @break

    @case(TutorialIllustration::Market)
        <div class="rounded-card bg-primary p-3">
            <div class="flex items-center gap-2 text-xs font-semibold">
                <span class="flex items-center gap-1.5 rounded-full bg-active px-3 py-1.5 text-on-active">
                    <x-icona :is="Icon::Shop" class="h-4 w-4" /> Emporio
                </span>
                <span class="flex items-center gap-1.5 rounded-full px-3 py-1.5 text-on-primary-soft">
                    <x-icona :is="Icon::Listings" class="h-4 w-4" /> Annunci
                </span>
                <span class="flex items-center gap-1.5 rounded-full px-3 py-1.5 text-on-primary-soft">
                    <x-icona :is="Icon::Trades" class="h-4 w-4" /> Scambi
                </span>
            </div>
        </div>
        @break

    @case(TutorialIllustration::Closing)
        <div class="flex justify-center">
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-primary text-on-primary">
                <x-icona :is="Icon::Characters" class="h-8 w-8" />
            </span>
        </div>
        @break
@endswitch
