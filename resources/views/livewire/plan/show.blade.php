<div class="h-full">
<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$plan->title" icon="heroicon-o-rectangle-stack" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Regimen', 'href' => route('regimen.dashboard'), 'icon' => 'academic-cap'],
            ['label' => 'Kurse', 'href' => route('regimen.plans.index')],
            ['label' => $plan->code ? $plan->code : $plan->title, 'href' => route('regimen.plans.show', ['uuid' => $plan->uuid])],
        ]">
            <button @click="Alpine?.store('page') && (Alpine.store('page')['activityOpen'] = !Alpine.store('page')['activityOpen'])"
                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-sm rounded-lg text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors">
                @svg('heroicon-o-information-circle', 'w-4 h-4')
                <span class="hidden sm:inline">Info</span>
            </button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Lektionen" icon="heroicon-o-list-bullet" width="w-72" :defaultOpen="true">
            <nav class="p-3 space-y-1">
                @forelse($sessions as $i => $session)
                    @php
                        $isDone = isset($completedSet[$session->id]);
                        $isCurrent = $resumeSession && $resumeSession->id === $session->id && !$isDone;
                    @endphp
                    <a wire:navigate href="{{ route('regimen.sessions.show', ['uuid' => $session->uuid]) }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm {{ $isCurrent ? 'bg-[var(--ui-primary-5)] text-[var(--ui-primary)] font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-[var(--ui-muted-5)]' }}">
                        <span class="flex-shrink-0 w-5 h-5 rounded-full text-[10px] flex items-center justify-center {{ $isDone ? 'bg-emerald-500 text-white' : 'bg-[var(--ui-muted-10)] text-gray-500' }}" style="font-family: var(--ui-font-mono);">
                            @if($isDone) @svg('heroicon-s-check', 'w-3 h-3') @else {{ $i + 1 }} @endif
                        </span>
                        <span class="flex-1 truncate">{{ $session->title }}</span>
                    </a>
                @empty
                    <div class="px-3 py-2 text-xs text-gray-400">Noch keine Lektionen.</div>
                @endforelse
            </nav>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Kurs" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-5 space-y-5">
                <div>
                    <h3 class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">Meta</h3>
                    <div class="space-y-2 text-xs text-gray-500 dark:text-gray-400">
                        @if($plan->code)
                            <div><span class="font-medium text-gray-700 dark:text-gray-300">Code:</span> {{ $plan->code }}</div>
                        @endif
                        @if($plan->category)
                            <div><span class="font-medium text-gray-700 dark:text-gray-300">Kategorie:</span> {{ $plan->category->title }}</div>
                        @endif
                        @if($plan->levelLabel())
                            <div><span class="font-medium text-gray-700 dark:text-gray-300">Level:</span> {{ $plan->levelLabel() }}</div>
                        @endif
                        @if($plan->target_audience)
                            <div><span class="font-medium text-gray-700 dark:text-gray-300">Für wen:</span> {{ $plan->target_audience }}</div>
                        @endif
                        <div><span class="font-medium text-gray-700 dark:text-gray-300">Status:</span> {{ $plan->status }}</div>
                    </div>
                </div>

                @if($enrollment)
                    <button wire:click="drop"
                            wire:confirm="Kurs wirklich verlassen? Dein Lektions-Fortschritt bleibt erhalten."
                            class="w-full text-center text-xs text-gray-400 hover:text-red-500 transition">
                        Kurs verlassen
                    </button>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    @php
        $coverColor = $plan->coverColor();
        $totalMin = $sessions->sum('estimated_minutes');
        $code = $plan->code ?: strtoupper(\Illuminate\Support\Str::substr($plan->slug, 0, 6));
        $prefix = $plan->category?->code_prefix ?: (string) \Illuminate\Support\Str::of($code)->before('-');
    @endphp

    <x-ui-page-container>
        <div class="max-w-5xl mx-auto space-y-8">

            {{-- ===== PFLICHT-BANNER ===== --}}
            @if($assignment)
                @php
                    $aOverdue = $assignment->status === \Platform\Regimen\Models\RegimenUserAssignment::STATUS_OVERDUE;
                    $aDue = $assignment->due_at;
                @endphp
                <div class="flex items-center gap-3 rounded-2xl border px-4 py-3 {{ $aOverdue ? 'border-red-500/40 bg-red-500/[0.06]' : 'border-[var(--ui-primary)]/30 bg-[var(--ui-primary-5)]' }}">
                    <span class="flex-shrink-0 w-9 h-9 rounded-xl flex items-center justify-center {{ $aOverdue ? 'bg-red-500/15 text-red-600 dark:text-red-400' : 'bg-[var(--ui-primary-10)] text-[var(--ui-primary)]' }}">
                        @svg('heroicon-o-flag', 'w-5 h-5')
                    </span>
                    <div class="min-w-0 text-sm">
                        <div class="font-semibold text-gray-900 dark:text-gray-100">
                            {{ $assignment->is_mandatory ? 'Dieser Kurs ist dir als Pflicht zugewiesen.' : 'Dieser Kurs wurde dir empfohlen.' }}
                        </div>
                        <div class="text-[13px] {{ $aOverdue ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                            @if($aOverdue)
                                Überfällig{{ $aDue ? ' seit '.$aDue->format('d.m.Y') : '' }} — bitte bald abschließen.
                            @elseif($aDue)
                                Bitte bis <span class="font-medium">{{ $aDue->format('d.m.Y') }}</span> abschließen.
                            @else
                                Kein festes Enddatum.
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- ===== HERO ===== --}}
            <div class="relative overflow-hidden rounded-3xl text-white shadow-lg"
                 style="background-image: linear-gradient(135deg, color-mix(in srgb, {{ $coverColor }} 92%, #ffffff), color-mix(in srgb, {{ $coverColor }} 62%, #000000));">
                <div class="pointer-events-none absolute inset-0" style="background-image: repeating-linear-gradient(115deg, rgba(255,255,255,.06) 0 1px, transparent 1px 26px);"></div>
                <div class="pointer-events-none absolute -right-6 -bottom-16 font-bold leading-none select-none" style="font-family: var(--ui-font-mono); font-size: 220px; color: rgba(255,255,255,.12); letter-spacing: -.05em;">{{ $prefix }}</div>

                <div class="relative z-10 p-8 md:p-11 flex flex-col gap-5">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-[11px] font-bold tracking-wider px-2.5 py-1 rounded-full bg-white/15 backdrop-blur" style="font-family: var(--ui-font-mono);">{{ $code }}</span>
                        @if($plan->category)
                            <span class="text-[11px] font-semibold uppercase tracking-wider px-2.5 py-1 rounded-full bg-white/15 backdrop-blur">{{ $plan->category->title }}</span>
                        @endif
                        @if($plan->levelLabel())
                            <span class="text-[11px] font-semibold uppercase tracking-wider px-2.5 py-1 rounded-full bg-white/90 text-gray-900" style="font-family: var(--ui-font-mono);">{{ $plan->levelLabel() }}</span>
                        @endif
                    </div>

                    <h1 class="text-3xl md:text-[2.6rem] leading-[1.08] font-bold tracking-tight max-w-3xl" style="font-family: var(--ui-font-mono); text-wrap: balance;">{{ $plan->title }}</h1>

                    @if($plan->description)
                        <p class="text-[15px] md:text-base leading-relaxed text-white/85 max-w-2xl">{{ $plan->description }}</p>
                    @endif

                    <div class="flex items-center gap-4 text-sm text-white/80" style="font-family: var(--ui-font-mono);">
                        <span class="inline-flex items-center gap-1.5">@svg('heroicon-o-rectangle-stack', 'w-4 h-4') {{ $summary['total'] }} {{ $summary['total'] == 1 ? 'Lektion' : 'Lektionen' }}</span>
                        @if($totalMin)
                            <span class="inline-flex items-center gap-1.5">@svg('heroicon-o-clock', 'w-4 h-4') ~{{ $totalMin }} min</span>
                        @endif
                    </div>

                    {{-- CTA --}}
                    <div class="pt-1">
                        @if($enrollment)
                            @if($enrollment->isCompleted())
                                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                    <div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/95 text-sm font-semibold" style="color: {{ $coverColor }};">
                                        @svg('heroicon-s-check-badge', 'w-5 h-5') Kurs abgeschlossen
                                    </div>
                                    @if($certificate)
                                        <a wire:navigate href="{{ route('regimen.certificates.show', ['uuid' => $certificate->uuid]) }}"
                                           class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-white/15 backdrop-blur border border-white/30 text-sm font-bold text-white hover:bg-white/25 transition">
                                            @svg('heroicon-o-document-check', 'w-5 h-5') Zertifikat ansehen
                                        </a>
                                    @endif
                                </div>
                            @else
                                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                    @if($resumeSession)
                                        <a wire:navigate href="{{ route('regimen.sessions.show', ['uuid' => $resumeSession->uuid]) }}"
                                           class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-white text-sm font-bold shadow-md hover:shadow-lg hover:scale-[1.02] transition" style="color: {{ $coverColor }};">
                                            Weiterlernen @svg('heroicon-s-arrow-right', 'w-4 h-4')
                                        </a>
                                    @endif
                                    <div class="flex-1 max-w-xs">
                                        <div class="flex items-center justify-between text-xs text-white/80 mb-1" style="font-family: var(--ui-font-mono);">
                                            <span>{{ $summary['completed'] }} / {{ $summary['total'] }}</span>
                                            <span>{{ $summary['pct'] }}%</span>
                                        </div>
                                        <div class="w-full bg-white/25 rounded-full h-2">
                                            <div class="h-2 rounded-full bg-white" style="width: {{ $summary['pct'] }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @else
                            <button wire:click="enroll"
                                    class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-white text-sm font-bold shadow-md hover:shadow-lg hover:scale-[1.02] transition" style="color: {{ $coverColor }};">
                                @svg('heroicon-o-plus', 'w-5 h-5') In Kurs einschreiben
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ===== LEHRPLAN ===== --}}
            <div>
                <div class="flex items-baseline justify-between mb-4">
                    <h2 class="text-lg font-bold tracking-tight text-gray-900 dark:text-gray-100" style="font-family: var(--ui-font-mono);">Lehrplan</h2>
                    <span class="text-xs text-gray-400" style="font-family: var(--ui-font-mono);">{{ $summary['completed'] }}/{{ $summary['total'] }} erledigt</span>
                </div>

                @if($sessions->isEmpty())
                    <div class="p-6 text-center rounded-2xl border border-[var(--ui-border)] bg-[var(--ui-muted-5)] text-gray-500 dark:text-gray-400">
                        Diesem Kurs sind noch keine Lektionen zugeordnet.
                    </div>
                @else
                    <ol class="space-y-2">
                        @foreach($sessions as $i => $session)
                            @php
                                $isDone = isset($completedSet[$session->id]);
                                $isCurrent = $resumeSession && $resumeSession->id === $session->id && !$isDone;
                            @endphp
                            <li>
                                <a wire:navigate href="{{ route('regimen.sessions.show', ['uuid' => $session->uuid]) }}"
                                   class="group flex items-center gap-4 p-4 rounded-2xl border transition-all hover:shadow-md hover:-translate-y-0.5
                                          {{ $isCurrent ? 'border-[var(--ui-primary)]/40 bg-[var(--ui-primary-5)]' : 'border-[var(--ui-border)] bg-[var(--ui-surface)] hover:bg-[var(--ui-muted-5)]' }}">
                                    <div class="flex-shrink-0 w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold
                                        @if($isDone) bg-emerald-500/15 text-emerald-600 dark:text-emerald-400
                                        @elseif($isCurrent) bg-[var(--ui-primary)] text-white
                                        @else bg-[var(--ui-muted-5)] border border-[var(--ui-border)] text-gray-500 dark:text-gray-400 @endif"
                                        style="font-family: var(--ui-font-mono);">
                                        @if($isDone) @svg('heroicon-s-check', 'w-4 h-4') @else {{ $i + 1 }} @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-semibold text-[15px] text-gray-900 dark:text-gray-100">{{ $session->title }}</span>
                                            @if($isCurrent)
                                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-[var(--ui-primary-10)] text-[var(--ui-primary)]" style="font-family: var(--ui-font-mono);">Weiter hier</span>
                                            @endif
                                        </div>
                                        @if($session->summary)
                                            <p class="text-[13px] text-gray-500 dark:text-gray-400 mt-0.5 leading-relaxed line-clamp-1">{{ $session->summary }}</p>
                                        @endif
                                    </div>
                                    @if($session->estimated_minutes)
                                        <div class="flex-shrink-0 text-[11px] text-gray-400 whitespace-nowrap" style="font-family: var(--ui-font-mono);">{{ $session->estimated_minutes }} min</div>
                                    @endif
                                    @svg('heroicon-o-chevron-right', 'w-5 h-5 text-gray-300 group-hover:text-gray-400 flex-shrink-0 transition')
                                </a>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

        </div>
    </x-ui-page-container>
</x-ui-page>
</div>
