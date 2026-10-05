<div class="h-full">
<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Regimen" icon="heroicon-o-academic-cap" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Regimen', 'href' => route('regimen.dashboard'), 'icon' => 'academic-cap'],
        ]">
            <button @click="Alpine?.store('page') && (Alpine.store('page')['activityOpen'] = !Alpine.store('page')['activityOpen'])"
                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-sm rounded-lg text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors">
                @svg('heroicon-o-chart-bar', 'w-4 h-4')
                <span class="hidden sm:inline">Aktivität</span>
            </button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Kategorien" icon="heroicon-o-squares-2x2" width="w-64" :defaultOpen="true">
            <nav class="p-3 space-y-1">
                <a wire:navigate href="{{ route('regimen.plans.index') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-[var(--ui-muted-5)]">
                    @svg('heroicon-o-rectangle-stack', 'w-4 h-4 text-[var(--ui-secondary)]')
                    <span class="flex-1 truncate">Alle Kurse</span>
                </a>
                @foreach($categories as $cat)
                    <a wire:navigate href="{{ route('regimen.plans.index', ['category' => $cat->slug]) }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-[var(--ui-muted-5)]">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" style="background: {{ $cat->color() }};"></span>
                        <span class="flex-1 truncate">{{ $cat->title }}</span>
                        <span class="text-[10px] text-gray-400" style="font-family: var(--ui-font-mono);">{{ $cat->plans_count }}</span>
                    </a>
                @endforeach
            </nav>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Regimen" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-5 space-y-5">
                <div>
                    <h3 class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">Dein Fortschritt</h3>
                    <div class="space-y-2">
                        <div class="p-3 rounded-lg bg-emerald-500/5 border border-emerald-500/15">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-600 dark:text-gray-300">Abgeschlossene Lektionen</span>
                                <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ $completedCount }}</span>
                            </div>
                        </div>
                        <div class="p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Abonnierte Kurse</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $enrolledCount }}</span>
                            </div>
                        </div>
                        <div class="p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Lektionen verfügbar</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $sessionsCount }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-10">

            {{-- HERO --}}
            <div>
                <div class="text-[11px] font-medium uppercase tracking-[0.16em] text-gray-400" style="font-family: var(--ui-font-mono);">Willkommen zurück</div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100 mt-1" style="font-family: var(--ui-font-mono);">
                    Weiter geht's, <span class="text-[var(--ui-primary)]">{{ $firstName }}.</span>
                </h1>
                <p class="text-[15px] text-gray-500 dark:text-gray-400 mt-2 max-w-xl">
                    @if($completedThisWeek > 0)
                        Du hast diese Woche {{ $completedThisWeek }} {{ $completedThisWeek == 1 ? 'Lektion' : 'Lektionen' }} abgeschlossen. Mach da weiter, wo du aufgehört hast.
                    @elseif($activePlans->isNotEmpty())
                        Deine Kurse warten auf dich — mach da weiter, wo du aufgehört hast.
                    @else
                        Schreib dich in deinen ersten Kurs ein und starte deinen Lernpfad.
                    @endif
                </p>
            </div>

            {{-- PFLICHT / ZUGEWIESEN --}}
            @if($assignments->isNotEmpty())
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        @svg('heroicon-o-flag', 'w-5 h-5 text-[var(--ui-primary)]')
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Für dich zu erledigen</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($assignments as $r)
                            @php
                                $ua = $r['ua'];
                                $p = $r['plan'];
                                $due = $ua->due_at;
                                $overdue = $ua->status === \Platform\Regimen\Models\RegimenUserAssignment::STATUS_OVERDUE;
                                $pct = (int) ($r['progress']['pct'] ?? 0);
                            @endphp
                            <div class="rounded-2xl border bg-[var(--ui-surface)] overflow-hidden flex flex-col shadow-sm hover:shadow-md transition-shadow {{ $overdue ? 'border-red-500/40' : 'border-[var(--ui-primary)]/30' }}">
                                <div class="px-4 py-2 flex items-center justify-between text-[11px] font-semibold {{ $overdue ? 'bg-red-500/10 text-red-600 dark:text-red-400' : 'bg-[var(--ui-primary-10)] text-[var(--ui-primary)]' }}" style="font-family: var(--ui-font-mono);">
                                    <span class="inline-flex items-center gap-1">
                                        @svg('heroicon-o-flag', 'w-3.5 h-3.5')
                                        {{ $ua->is_mandatory ? 'Pflicht' : 'Empfohlen' }}
                                    </span>
                                    <span>
                                        @if($overdue)
                                            Überfällig{{ $due ? ' · '.$due->format('d.m.Y') : '' }}
                                        @elseif($due)
                                            Fällig {{ $due->format('d.m.Y') }}
                                        @else
                                            Kein Enddatum
                                        @endif
                                    </span>
                                </div>
                                <div class="p-4 flex flex-col gap-2.5 flex-1">
                                    @if($p->category)
                                        <span class="text-[10px] font-semibold uppercase tracking-wider" style="font-family: var(--ui-font-mono); color: {{ $p->coverColor() }};">{{ $p->category->title }}</span>
                                    @endif
                                    <a wire:navigate href="{{ route('regimen.plans.show', ['uuid' => $p->uuid]) }}" class="font-semibold text-[15px] text-gray-900 dark:text-gray-100 leading-tight hover:text-[var(--ui-primary)] transition-colors">{{ $p->title }}</a>
                                    <div class="w-full bg-[var(--ui-muted-10)] rounded-full h-1.5 mt-1">
                                        <div class="h-1.5 rounded-full {{ $overdue ? 'bg-red-500' : 'bg-[var(--ui-primary)]' }}" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px] text-gray-400" style="font-family: var(--ui-font-mono);">
                                        <span>{{ $r['progress']['completed'] ?? 0 }} / {{ $r['progress']['total'] ?? 0 }} Lektionen</span>
                                        <span>{{ $pct }}%</span>
                                    </div>
                                    <a wire:navigate href="{{ route('regimen.plans.show', ['uuid' => $p->uuid]) }}"
                                       class="mt-1 flex items-center justify-center gap-2 w-full px-4 py-2 rounded-lg text-white text-[13px] font-semibold hover:opacity-90 transition {{ $overdue ? 'bg-red-500' : 'bg-[var(--ui-primary)]' }}">
                                        {{ $pct > 0 ? 'Fortsetzen' : 'Starten' }} @svg('heroicon-s-arrow-right', 'w-4 h-4')
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ZWEI WEGE ZU LERNEN --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a wire:navigate href="{{ route('regimen.plans.index') }}"
                   class="group rounded-2xl border border-[var(--ui-border)] bg-[var(--ui-surface)] p-5 flex gap-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                    <span class="flex-shrink-0 w-11 h-11 rounded-xl flex items-center justify-center bg-[var(--ui-primary-10)] text-[var(--ui-primary)]">
                        @svg('heroicon-o-rectangle-stack', 'w-5 h-5')
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold text-[15px] text-gray-900 dark:text-gray-100">Kurse</h3>
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-[var(--ui-primary-10)] text-[var(--ui-primary)]" style="font-family: var(--ui-font-mono);">geführt</span>
                        </div>
                        <p class="text-[13px] text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">Kuratierte Lernpfade in fester Reihenfolge — einschreiben, dranbleiben, abschließen.</p>
                        <span class="inline-flex items-center gap-1 text-[12px] font-semibold text-[var(--ui-primary)] mt-2">Zu den Kursen @svg('heroicon-o-arrow-right', 'w-3.5 h-3.5')</span>
                    </div>
                </a>
                <a wire:navigate href="{{ route('regimen.topics.index') }}"
                   class="group rounded-2xl border border-[var(--ui-border)] bg-[var(--ui-surface)] p-5 flex gap-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                    <span class="flex-shrink-0 w-11 h-11 rounded-xl flex items-center justify-center bg-[var(--ui-muted-10)] text-gray-600 dark:text-gray-300">
                        @svg('heroicon-o-book-open', 'w-5 h-5')
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold text-[15px] text-gray-900 dark:text-gray-100">Bibliothek</h3>
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-[var(--ui-muted-10)] text-gray-500 dark:text-gray-400" style="font-family: var(--ui-font-mono);">frei</span>
                        </div>
                        <p class="text-[13px] text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">Alle Lektionen nach Thema. Frei stöbern und einzelne herauspicken — ohne Reihenfolge.</p>
                        <span class="inline-flex items-center gap-1 text-[12px] font-semibold text-gray-700 dark:text-gray-300 mt-2">Zur Bibliothek @svg('heroicon-o-arrow-right', 'w-3.5 h-3.5')</span>
                    </div>
                </a>
            </div>

            {{-- MEINE REGIMEN --}}
            @if($activePlans->isNotEmpty())
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Meine Regimen</h2>
                        <a wire:navigate href="{{ route('regimen.plans.index') }}" class="text-xs font-semibold text-[var(--ui-primary)] hover:underline">Alle Kurse →</a>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($activePlans as $row)
                            @php($p = $row['plan'])
                            <div class="rounded-2xl border border-[var(--ui-border)] bg-[var(--ui-surface)] overflow-hidden flex flex-col shadow-sm hover:shadow-md transition-shadow">
                                <a wire:navigate href="{{ route('regimen.plans.show', ['uuid' => $p->uuid]) }}">
                                    @include('regimen::partials.plan-cover', ['plan' => $p, 'size' => 'rail'])
                                </a>
                                <div class="p-4 flex flex-col gap-2.5 flex-1">
                                    @if($p->category)
                                        <span class="text-[10px] font-semibold uppercase tracking-wider" style="font-family: var(--ui-font-mono); color: {{ $p->coverColor() }};">{{ $p->category->title }}</span>
                                    @endif
                                    <a wire:navigate href="{{ route('regimen.plans.show', ['uuid' => $p->uuid]) }}" class="font-semibold text-[15px] text-gray-900 dark:text-gray-100 leading-tight hover:text-[var(--ui-primary)] transition-colors">{{ $p->title }}</a>
                                    @if($row['resume'])
                                        <div class="text-[12px] text-gray-500 dark:text-gray-400">Weiter bei · <span class="font-medium text-gray-700 dark:text-gray-300">{{ $row['resume']->title }}</span></div>
                                    @endif
                                    <div class="w-full bg-[var(--ui-muted-10)] rounded-full h-1.5 mt-1">
                                        <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $row['progress']['pct'] }}%"></div>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px] text-gray-400" style="font-family: var(--ui-font-mono);">
                                        <span>{{ $row['progress']['completed'] }} / {{ $row['progress']['total'] }} Lektionen</span>
                                        <span>{{ $row['progress']['pct'] }}%</span>
                                    </div>
                                    <a wire:navigate href="{{ route('regimen.plans.show', ['uuid' => $p->uuid]) }}"
                                       class="mt-1 flex items-center justify-center gap-2 w-full px-4 py-2 rounded-lg bg-[var(--ui-primary)] text-white text-[13px] font-semibold hover:opacity-90 transition">
                                        Weiterlernen @svg('heroicon-s-arrow-right', 'w-4 h-4')
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- MEINE ZERTIFIKATE --}}
            @if($completedPlans->isNotEmpty())
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        @svg('heroicon-o-document-check', 'w-5 h-5 text-emerald-500')
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Meine Zertifikate</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($completedPlans as $row)
                            @php($p = $row['plan'])
                            <div class="rounded-2xl border border-emerald-500/25 bg-emerald-500/[0.04] p-5 flex flex-col gap-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        @if($p->code)
                                            <span class="text-[10px] font-bold tracking-wider px-2 py-0.5 rounded-full text-white" style="font-family: var(--ui-font-mono); background: {{ $p->coverColor() }};">{{ $p->code }}</span>
                                        @endif
                                        <div class="mt-2 font-semibold text-[15px] text-gray-900 dark:text-gray-100 leading-tight">{{ $p->title }}</div>
                                    </div>
                                    @svg('heroicon-s-check-badge', 'w-7 h-7 text-emerald-500 flex-shrink-0')
                                </div>
                                @if($row['certificate'])
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400" style="font-family: var(--ui-font-mono);">{{ $row['certificate']->serial }}</div>
                                    <a wire:navigate href="{{ route('regimen.certificates.show', ['uuid' => $row['certificate']->uuid]) }}"
                                       class="mt-auto flex items-center justify-center gap-2 w-full px-4 py-2 rounded-lg bg-[var(--ui-primary)] text-white text-[13px] font-semibold hover:opacity-90 transition">
                                        @svg('heroicon-o-document-check', 'w-4 h-4') Zertifikat ansehen
                                    </a>
                                @else
                                    <div class="mt-auto text-[12px] text-emerald-600 dark:text-emerald-400 font-medium">Abgeschlossen</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- KATALOG --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $activePlans->isNotEmpty() ? 'Kurse entdecken' : 'Kurskatalog' }}
                    </h2>
                    <a wire:navigate href="{{ route('regimen.plans.index') }}" class="text-xs font-semibold text-[var(--ui-primary)] hover:underline">Alle ansehen →</a>
                </div>

                @if($discover->isEmpty())
                    <div class="p-6 text-center rounded-2xl border border-[var(--ui-border)] bg-[var(--ui-muted-5)] text-gray-500 dark:text-gray-400">
                        @if($activePlans->isNotEmpty())
                            Du bist in alle verfügbaren Kurse eingeschrieben. 🎉
                        @else
                            Noch keine Kurse veröffentlicht.
                        @endif
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($discover as $p)
                            <a wire:navigate href="{{ route('regimen.plans.show', ['uuid' => $p->uuid]) }}"
                               class="group rounded-2xl border border-[var(--ui-border)] bg-[var(--ui-surface)] overflow-hidden flex flex-col shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                                @include('regimen::partials.plan-cover', ['plan' => $p, 'size' => 'card'])
                                <div class="p-4 flex flex-col gap-2 flex-1">
                                    @if($p->category)
                                        <span class="text-[10px] font-semibold uppercase tracking-wider" style="font-family: var(--ui-font-mono); color: {{ $p->coverColor() }};">{{ $p->category->title }}</span>
                                    @endif
                                    <h3 class="font-semibold text-[15px] text-gray-900 dark:text-gray-100 leading-tight">{{ $p->title }}</h3>
                                    @if($p->description)
                                        <p class="text-[13px] text-gray-500 dark:text-gray-400 leading-relaxed line-clamp-2">{{ $p->description }}</p>
                                    @endif
                                    <div class="flex items-center gap-3 text-[11px] text-gray-400 mt-auto pt-1" style="font-family: var(--ui-font-mono);">
                                        <span>{{ $p->sessions_count }} {{ $p->sessions_count == 1 ? 'Lektion' : 'Lektionen' }}</span>
                                        @if($p->levelLabel())<span>· {{ $p->levelLabel() }}</span>@endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </x-ui-page-container>
</x-ui-page>
</div>
