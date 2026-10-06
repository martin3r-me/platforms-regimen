<div class="h-full">
<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$topic->title" icon="heroicon-o-book-open" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Regimen', 'href' => route('regimen.dashboard'), 'icon' => 'bolt'],
            ['label' => 'Bibliothek', 'href' => route('regimen.topics.index')],
            ['label' => $topic->title, 'href' => route('regimen.topics.show', ['uuid' => $topic->uuid])],
        ]">
            <button @click="Alpine?.store('page') && (Alpine.store('page')['activityOpen'] = !Alpine.store('page')['activityOpen'])"
                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-sm rounded-lg text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors">
                @svg('heroicon-o-information-circle', 'w-4 h-4')
                <span class="hidden sm:inline">Info</span>
            </button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Einheiten" icon="heroicon-o-list-bullet" width="w-72" :defaultOpen="true">
            <nav class="p-3 space-y-1">
                @forelse($sessions as $i => $session)
                    @php($isDone = isset($completedSet[$session->id]))
                    <a wire:navigate href="{{ route('regimen.sessions.show', ['uuid' => $session->uuid]) }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-[var(--ui-muted-5)]">
                        <span class="flex-shrink-0 w-5 h-5 rounded-full text-[10px] flex items-center justify-center {{ $isDone ? 'bg-emerald-500 text-white' : 'bg-[var(--ui-muted-10)] text-gray-500' }}" style="font-family: var(--ui-font-mono);">
                            @if($isDone) @svg('heroicon-s-check', 'w-3 h-3') @else {{ $i + 1 }} @endif
                        </span>
                        <span class="flex-1 truncate">{{ $session->title }}</span>
                    </a>
                @empty
                    <div class="px-3 py-2 text-xs text-gray-400">Noch keine Einheiten.</div>
                @endforelse
            </nav>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Thema" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-5 space-y-5">
                <div>
                    <h3 class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">Fortschritt</h3>
                    <div class="p-3 rounded-lg bg-emerald-500/5 border border-emerald-500/15">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-gray-600 dark:text-gray-300">{{ $done }} / {{ $total }}</span>
                            <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ $pct }}%</span>
                        </div>
                        <div class="w-full bg-emerald-500/10 rounded-full h-1.5">
                            <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">Meta</h3>
                    <div class="space-y-2 text-xs text-gray-500 dark:text-gray-400">
                        <div><span class="font-medium text-gray-700 dark:text-gray-300">Slug:</span> {{ $topic->slug }}</div>
                        <div><span class="font-medium text-gray-700 dark:text-gray-300">Erstellt:</span> {{ $topic->created_at?->format('d.m.Y') }}</div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        <div class="max-w-3xl mx-auto space-y-6">

            <div>
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-11 h-11 rounded-xl flex items-center justify-center"
                         style="background: color-mix(in srgb, {{ $topic->hexColor() }} 13%, transparent); color: {{ $topic->hexColor() }};">
                        @svg($topic->icon ?: 'heroicon-o-folder', 'w-6 h-6')
                    </div>
                    <div class="min-w-0">
                        <div class="text-[11px] font-medium uppercase tracking-[0.14em]" style="font-family: var(--ui-font-mono); color: {{ $topic->hexColor() }};">Bibliothek · Thema</div>
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100" style="font-family: var(--ui-font-mono);">{{ $topic->title }}</h1>
                    </div>
                </div>
                @if($topic->description)
                    <p class="text-[15px] leading-relaxed text-gray-600 dark:text-gray-300 mt-3">{{ $topic->description }}</p>
                @endif

                @if($total > 0)
                    <div class="mt-4 flex items-center gap-3">
                        <div class="flex-1 max-w-xs bg-[var(--ui-muted-10)] rounded-full h-2">
                            <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400" style="font-family: var(--ui-font-mono);">{{ $done }} / {{ $total }} abgeschlossen</span>
                    </div>
                @endif
            </div>

            @if($sessions->isEmpty())
                <div class="p-6 text-center rounded-2xl border border-[var(--ui-border)] bg-[var(--ui-muted-5)] text-gray-500 dark:text-gray-400">
                    Noch keine veröffentlichten Einheiten in diesem Thema.
                </div>
            @else
                <ol class="divide-y divide-[var(--ui-border)] border-y border-[var(--ui-border)]">
                    @foreach($sessions as $i => $session)
                        @php($isDone = isset($completedSet[$session->id]))
                        <li>
                            <a wire:navigate href="{{ route('regimen.sessions.show', ['uuid' => $session->uuid]) }}"
                               class="group flex gap-4 py-4 hover:bg-[var(--ui-muted-5)] -mx-3 px-3 rounded-lg transition">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center text-sm font-semibold
                                    @if($isDone) bg-emerald-500/15 text-emerald-600 dark:text-emerald-400
                                    @else bg-[var(--ui-muted-5)] border border-[var(--ui-border)] text-gray-500 dark:text-gray-400 @endif"
                                    style="font-family: var(--ui-font-mono);">
                                    @if($isDone) @svg('heroicon-s-check', 'w-4 h-4') @else {{ $i + 1 }} @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-[15px] text-gray-900 dark:text-gray-100">{{ $session->title }}</div>
                                    @if($session->summary)
                                        <p class="text-[13px] text-gray-500 dark:text-gray-400 mt-0.5 leading-relaxed line-clamp-2">{{ $session->summary }}</p>
                                    @endif
                                </div>
                                @if($session->estimated_minutes)
                                    <div class="flex-shrink-0 text-[11px] text-gray-400 whitespace-nowrap pt-1" style="font-family: var(--ui-font-mono);">{{ $session->estimated_minutes }} min</div>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </x-ui-page-container>
</x-ui-page>
</div>
