<div class="h-full">
<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$plan->title" icon="heroicon-o-calendar-days" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Regimen', 'href' => route('regimen.dashboard'), 'icon' => 'bolt'],
            ['label' => 'Pläne', 'href' => route('regimen.plans.index')],
            ['label' => $plan->title, 'href' => route('regimen.plans.show', ['uuid' => $plan->uuid])],
            ['label' => 'Mein Trainingsplan', 'href' => route('regimen.plans.schedule', ['uuid' => $plan->uuid])],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="max-w-3xl mx-auto space-y-8">

            @php
                $wd = [1=>'Mo',2=>'Di',3=>'Mi',4=>'Do',5=>'Fr',6=>'Sa',7=>'So'];
            @endphp

            {{-- Kopf + Fortschritt --}}
            <div>
                <div class="text-[11px] font-medium uppercase tracking-[0.16em] text-gray-400" style="font-family: var(--ui-font-mono);">Mein Trainingsplan</div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 mt-1" style="font-family: var(--ui-font-mono);">{{ $plan->title }}</h1>

                @if($enrollment && $enrollment->start_date)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Start {{ $enrollment->start_date->format('d.m.Y') }}
                        @if($total > 0) · {{ $done }}/{{ $total }} erledigt @endif
                    </p>
                @endif

                @if($total > 0)
                    <div class="mt-3 flex items-center gap-3">
                        <div class="flex-1 bg-[var(--ui-muted-10)] rounded-full h-2">
                            <div class="h-2 rounded-full" style="width: {{ $pct }}%; background: {{ $accentColor }};"></div>
                        </div>
                        <span class="text-[12px] font-semibold text-gray-500 dark:text-gray-400" style="font-family: var(--ui-font-mono);">{{ $pct }}%</span>
                    </div>
                @endif
            </div>

            @if($total === 0)
                <div class="p-6 text-center rounded-2xl border border-[var(--ui-border)] bg-[var(--ui-muted-5)] text-gray-500 dark:text-gray-400">
                    Noch kein datierter Plan. Sobald dir der Plan mit Startdatum zugewiesen ist, erscheinen hier die Einheiten Tag für Tag.
                </div>
            @else
                @foreach($weeks as $week => $items)
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400" style="font-family: var(--ui-font-mono);">Woche {{ $week }}</h2>
                            <div class="flex-1 h-px bg-[var(--ui-border)]"></div>
                        </div>

                        <div class="space-y-2">
                            @foreach($items as $entry)
                                @php $isDone = $entry->isCompleted(); @endphp
                                <div class="flex items-center gap-3 rounded-2xl border px-4 py-3 transition
                                            {{ $isDone ? 'border-emerald-500/30 bg-emerald-500/[0.06]' : 'border-[var(--ui-border)] bg-[var(--ui-surface)]' }}">
                                    {{-- Datum --}}
                                    <div class="flex-shrink-0 w-14 text-center">
                                        <div class="text-[11px] uppercase text-gray-400" style="font-family: var(--ui-font-mono);">{{ $wd[$entry->scheduled_date->dayOfWeekIso] ?? '' }}</div>
                                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-200" style="font-family: var(--ui-font-mono);">{{ $entry->scheduled_date->format('d.m.') }}</div>
                                    </div>

                                    {{-- Inhalt --}}
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-semibold text-gray-900 dark:text-gray-100 {{ $isDone ? 'line-through opacity-70' : '' }}">{{ $entry->title }}</span>
                                            @if($entry->kindLabel())
                                                <span class="text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-[var(--ui-muted-10)] text-gray-500 dark:text-gray-400" style="font-family: var(--ui-font-mono);">{{ $entry->kindLabel() }}</span>
                                            @endif
                                        </div>
                                        @if($entry->targetLabel())
                                            <div class="text-[13px] text-gray-500 dark:text-gray-400" style="font-family: var(--ui-font-mono);">{{ $entry->targetLabel() }}</div>
                                        @endif
                                    </div>

                                    {{-- Abschluss pro Eintrag --}}
                                    <div class="flex-shrink-0">
                                        @if($isDone)
                                            <button wire:click="reopen({{ $entry->id }})"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-emerald-700 dark:text-emerald-300 hover:bg-emerald-500/10 transition">
                                                @svg('heroicon-s-check-circle', 'w-5 h-5 text-emerald-500') Erledigt
                                            </button>
                                        @else
                                            <button wire:click="markDone({{ $entry->id }})"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-[var(--ui-border)] text-xs font-semibold text-gray-600 dark:text-gray-300 hover:border-[var(--ui-primary)] hover:text-[var(--ui-primary)] transition">
                                                @svg('heroicon-o-check', 'w-4 h-4') Erledigt
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </x-ui-page-container>
</x-ui-page>
</div>
