<div class="h-full">
<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Pläne" icon="heroicon-o-rectangle-stack" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Regimen', 'href' => route('regimen.dashboard'), 'icon' => 'bolt'],
            ['label' => 'Pläne', 'href' => route('regimen.plans.index')],
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
                   class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm {{ !$activeCategory ? 'bg-[var(--ui-primary-5)] text-[var(--ui-primary)] font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-[var(--ui-muted-5)]' }}">
                    @svg('heroicon-o-rectangle-stack', 'w-4 h-4')
                    <span class="flex-1 truncate">Alle Pläne</span>
                </a>
                @foreach($categories as $cat)
                    <a wire:navigate href="{{ route('regimen.plans.index', ['category' => $cat->slug]) }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm {{ $activeCategory && $activeCategory->id === $cat->id ? 'bg-[var(--ui-primary-5)] text-[var(--ui-primary)] font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-[var(--ui-muted-5)]' }}">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" style="background: {{ $cat->color() }};"></span>
                        <span class="flex-1 truncate">{{ $cat->title }}</span>
                        <span class="text-[10px] text-gray-400" style="font-family: var(--ui-font-mono);">{{ $cat->plans_count }}</span>
                    </a>
                @endforeach
            </nav>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Pläne" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-5 space-y-3">
                <div class="p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03]">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Pläne</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $plans->count() }}</span>
                    </div>
                </div>
                <div class="p-3 rounded-lg bg-emerald-500/5 border border-emerald-500/15">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600 dark:text-gray-300">Dein Schnitt</span>
                        <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ $plans->isNotEmpty() ? round($plans->avg('progress_pct')) : 0 }}%</span>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">

            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100" style="font-family: var(--ui-font-mono);">Plankatalog</h1>
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-[var(--ui-primary-10)] text-[var(--ui-primary)]" style="font-family: var(--ui-font-mono);">geführt</span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-2xl">
                    Kuratierte Trainingspläne in fester Reihenfolge — schreib dich ein und arbeite dich mit Fortschritt durch.
                    Nur eine einzelne Einheit gesucht? <a wire:navigate href="{{ route('regimen.topics.index') }}" class="text-[var(--ui-primary)] font-medium hover:underline">Stöber frei in der Bibliothek</a>.
                </p>
            </div>

            @if($activeCategory)
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <span class="w-2.5 h-2.5 rounded-full" style="background: {{ $activeCategory->color() }};"></span>
                    Gefiltert nach <span class="font-medium text-gray-900 dark:text-gray-100">{{ $activeCategory->title }}</span>
                    <a wire:navigate href="{{ route('regimen.plans.index') }}" class="text-[var(--ui-primary)] hover:underline">· zurücksetzen</a>
                </div>
            @endif

            @if($plans->isEmpty())
                <div class="p-6 text-center rounded-2xl border border-[var(--ui-border)] bg-[var(--ui-muted-5)] text-gray-500 dark:text-gray-400">
                    @if($activeCategory)
                        In dieser Kategorie sind noch keine Pläne veröffentlicht.
                    @else
                        Noch keine Pläne veröffentlicht.
                    @endif
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($plans as $plan)
                        <a wire:navigate href="{{ route('regimen.plans.show', ['uuid' => $plan->uuid]) }}"
                           class="group rounded-2xl border border-[var(--ui-border)] bg-[var(--ui-surface)] overflow-hidden flex flex-col shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                            @include('regimen::partials.plan-cover', ['plan' => $plan, 'size' => 'card'])
                            <div class="p-4 flex flex-col gap-2 flex-1">
                                @if($plan->category)
                                    <span class="text-[10px] font-semibold uppercase tracking-wider" style="font-family: var(--ui-font-mono); color: {{ $plan->coverColor() }};">{{ $plan->category->title }}</span>
                                @endif
                                <h3 class="font-semibold text-[15px] text-gray-900 dark:text-gray-100 leading-tight">{{ $plan->title }}</h3>
                                @if($plan->description)
                                    <p class="text-[13px] text-gray-500 dark:text-gray-400 leading-relaxed line-clamp-2">{{ $plan->description }}</p>
                                @endif

                                <div class="mt-auto pt-2">
                                    @if(isset($enrolledSet[$plan->id]))
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-[var(--ui-muted-10)] rounded-full h-1.5">
                                                <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $plan->progress_pct }}%"></div>
                                            </div>
                                            <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400" style="font-family: var(--ui-font-mono);">{{ $plan->progress_pct }}%</span>
                                        </div>
                                    @else
                                        <div class="flex items-center justify-between">
                                            <span class="text-[11px] text-gray-400" style="font-family: var(--ui-font-mono);">
                                                {{ $plan->sessions_count }} {{ $plan->sessions_count == 1 ? 'Einheit' : 'Einheiten' }}@if($plan->levelLabel()) · {{ $plan->levelLabel() }}@endif
                                            </span>
                                            <span class="text-[11px] font-semibold text-[var(--ui-primary)] opacity-0 group-hover:opacity-100 transition-opacity" style="font-family: var(--ui-font-mono);">Ansehen →</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </x-ui-page-container>
</x-ui-page>
</div>
