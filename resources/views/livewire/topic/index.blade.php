<div class="h-full">
<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Bibliothek" icon="heroicon-o-book-open" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Regimen', 'href' => route('regimen.dashboard'), 'icon' => 'academic-cap'],
            ['label' => 'Bibliothek', 'href' => route('regimen.topics.index')],
        ]">
            <button @click="Alpine?.store('page') && (Alpine.store('page')['activityOpen'] = !Alpine.store('page')['activityOpen'])"
                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-sm rounded-lg text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors">
                @svg('heroicon-o-chart-bar', 'w-4 h-4')
                <span class="hidden sm:inline">Aktivität</span>
            </button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Themen" icon="heroicon-o-folder" width="w-64" :defaultOpen="true">
            <nav class="p-3 space-y-1">
                @forelse($topics as $topic)
                    <a wire:navigate href="{{ route('regimen.topics.show', ['uuid' => $topic->uuid]) }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-[var(--ui-muted-5)]">
                        <span class="flex-shrink-0" style="color: {{ $topic->hexColor() }};">@svg($topic->icon ?: 'heroicon-o-folder', 'w-4 h-4')</span>
                        <span class="flex-1 truncate">{{ $topic->title }}</span>
                        <span class="text-[10px] text-gray-400" style="font-family: var(--ui-font-mono);">{{ $topic->session_total }}</span>
                    </a>
                @empty
                    <div class="px-3 py-2 text-xs text-gray-400">Noch keine Themen.</div>
                @endforelse
            </nav>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Bibliothek" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-5 space-y-3">
                <div class="p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03]">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Themen</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $topics->count() }}</span>
                    </div>
                </div>
                <div class="p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03]">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Lektionen gesamt</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $sessionsTotal }}</span>
                    </div>
                </div>
                <div class="p-3 rounded-lg bg-emerald-500/5 border border-emerald-500/15">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600 dark:text-gray-300">Von dir abgeschlossen</span>
                        <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ $completedTotal }}</span>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">

            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100" style="font-family: var(--ui-font-mono);">Bibliothek</h1>
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-[var(--ui-muted-10)] text-gray-500 dark:text-gray-400" style="font-family: var(--ui-font-mono);">frei</span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-2xl">
                    Alle Lektionen nach Thema — stöber frei und pick dir einzelne heraus, ohne Reihenfolge oder Einschreiben.
                    Lieber geführt mit Fortschritt? <a wire:navigate href="{{ route('regimen.plans.index') }}" class="text-[var(--ui-primary)] font-medium hover:underline">Nimm die Kurse</a>.
                </p>
            </div>

            @if($topics->isEmpty())
                <div class="p-6 text-center rounded-2xl border border-[var(--ui-border)] bg-[var(--ui-muted-5)] text-gray-500 dark:text-gray-400">
                    Noch keine Themen angelegt.
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($topics as $topic)
                        @php($hex = $topic->hexColor())
                        <a wire:navigate href="{{ route('regimen.topics.show', ['uuid' => $topic->uuid]) }}"
                           class="group relative overflow-hidden rounded-2xl border border-[var(--ui-border)] bg-[var(--ui-surface)] p-5 flex flex-col shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                            <span class="absolute inset-x-0 top-0 h-1" style="background: {{ $hex }};"></span>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-11 h-11 rounded-xl flex items-center justify-center"
                                      style="background: color-mix(in srgb, {{ $hex }} 13%, transparent); color: {{ $hex }};">
                                    @svg($topic->icon ?: 'heroicon-o-folder', 'w-5 h-5')
                                </span>
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-semibold text-[15px] text-gray-900 dark:text-gray-100 leading-tight">{{ $topic->title }}</h3>
                                    <div class="text-[11px] font-semibold mt-0.5" style="font-family: var(--ui-font-mono); color: {{ $hex }};">
                                        {{ $topic->session_total }} {{ $topic->session_total === 1 ? 'Lektion' : 'Lektionen' }}
                                    </div>
                                </div>
                            </div>

                            @if($topic->description)
                                <p class="text-[13px] text-gray-500 dark:text-gray-400 mt-3 leading-relaxed line-clamp-2 flex-1">{{ $topic->description }}</p>
                            @else
                                <div class="flex-1"></div>
                            @endif

                            @if($topic->session_total > 0)
                                <div class="mt-4 flex items-center gap-2">
                                    <div class="flex-1 bg-[var(--ui-muted-10)] rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $topic->progress_pct }}%"></div>
                                    </div>
                                    <span class="text-[11px] font-semibold {{ $topic->progress_pct > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}" style="font-family: var(--ui-font-mono);">{{ $topic->session_done }}/{{ $topic->session_total }}</span>
                                </div>
                            @else
                                <div class="mt-4 text-[11px] text-gray-400" style="font-family: var(--ui-font-mono);">In Vorbereitung</div>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </x-ui-page-container>
</x-ui-page>
</div>
