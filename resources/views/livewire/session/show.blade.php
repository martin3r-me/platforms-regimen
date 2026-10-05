<div class="h-full">
{{-- Highlight.js: Syntax-Highlighting für Code-Blocks --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.10.0/build/styles/github-dark.min.css">
<script defer src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.10.0/build/highlight.min.js"></script>

{{-- Regimen-Reader-Styles: Callouts, Code-Blocks, Reading-Polish --}}
<style>
    /* === Callouts / GitHub-Alerts === */
    .regimen-alert {
        margin: 1.5rem 0;
        padding: 0.875rem 1rem;
        border-left: 4px solid;
        border-radius: 0.5rem;
        background: var(--ui-muted-5);
    }
    .regimen-alert-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }
    .regimen-alert-icon { width: 1.125rem; height: 1.125rem; flex-shrink: 0; }
    .regimen-alert-body {
        color: var(--ui-secondary);
        font-size: 0.9375rem;
        line-height: 1.6;
    }
    .regimen-alert-body > *:first-child { margin-top: 0; }
    .regimen-alert-body > *:last-child { margin-bottom: 0; }

    .regimen-alert-info     { border-left-color: #3b82f6; background: rgba(59,130,246,.06); }
    .regimen-alert-tip      { border-left-color: #10b981; background: rgba(16,185,129,.07); }
    .regimen-alert-warning  { border-left-color: #f59e0b; background: rgba(245,158,11,.07); }
    .regimen-alert-note     { border-left-color: #6b7280; background: var(--ui-muted-5); }
    .regimen-alert-important{ border-left-color: #d946ef; background: rgba(217,70,239,.06); }
    .regimen-alert-caution  { border-left-color: #ef4444; background: rgba(239,68,68,.06); }

    .regimen-alert-info     .regimen-alert-label { color: #1d4ed8; }
    .regimen-alert-tip      .regimen-alert-label { color: #047857; }
    .regimen-alert-warning  .regimen-alert-label { color: #b45309; }
    .regimen-alert-note     .regimen-alert-label { color: #374151; }
    .regimen-alert-important .regimen-alert-label { color: #a21caf; }
    .regimen-alert-caution  .regimen-alert-label { color: #b91c1c; }

    .dark .regimen-alert-info     .regimen-alert-label { color: #93c5fd; }
    .dark .regimen-alert-tip      .regimen-alert-label { color: #6ee7b7; }
    .dark .regimen-alert-warning  .regimen-alert-label { color: #fcd34d; }
    .dark .regimen-alert-note     .regimen-alert-label { color: #d1d5db; }
    .dark .regimen-alert-important .regimen-alert-label { color: #f0abfc; }
    .dark .regimen-alert-caution  .regimen-alert-label { color: #fca5a5; }

    /* === Typography: explizite Hierarchie ohne Verlass auf prose-Plugin === */
    .regimen-session-content {
        color: var(--ui-secondary);
        font-size: 1rem;
        line-height: 1.7;
    }
    .regimen-session-content > * + * { margin-top: 1em; }

    .regimen-session-content h1 {
        margin-top: 0;
        margin-bottom: 0.75em;
        font-size: 1.875rem;
        font-weight: 700;
        line-height: 1.2;
        color: var(--ui-primary);
    }
    .regimen-session-content h2 {
        margin-top: 2.5em;
        margin-bottom: 0.75em;
        padding-bottom: 0.4em;
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.3;
        color: var(--ui-primary);
        border-bottom: 1px solid var(--ui-border);
    }
    .regimen-session-content h3 {
        margin-top: 2em;
        margin-bottom: 0.5em;
        font-size: 1.125rem;
        font-weight: 600;
        line-height: 1.4;
        color: var(--ui-primary);
    }
    .regimen-session-content h4 {
        margin-top: 1.5em;
        margin-bottom: 0.4em;
        font-size: 1rem;
        font-weight: 600;
        color: var(--ui-primary);
    }
    /* Mono-Überschriften — Marken-Signatur (wie im Design-Deck) */
    .regimen-session-content h1,
    .regimen-session-content h2,
    .regimen-session-content h3,
    .regimen-session-content h4 {
        font-family: var(--ui-font-mono);
        letter-spacing: -0.01em;
    }
    .regimen-session-content p {
        margin: 0.75em 0;
        line-height: 1.7;
    }
    .regimen-session-content strong {
        color: var(--ui-primary);
        font-weight: 600;
    }
    .regimen-session-content em { font-style: italic; }

    /* Listen — explizite Bullets/Numbers, klare Einrueckung */
    .regimen-session-content ul,
    .regimen-session-content ol {
        margin: 1em 0;
        padding-left: 1.5em;
    }
    .regimen-session-content ul { list-style: disc; }
    .regimen-session-content ol { list-style: decimal; }
    .regimen-session-content li {
        margin: 0.4em 0;
        padding-left: 0.375em;
    }
    .regimen-session-content li::marker {
        color: var(--ui-muted);
        font-weight: 600;
    }
    .regimen-session-content li > p { margin: 0.25em 0; }
    .regimen-session-content li > ul,
    .regimen-session-content li > ol { margin: 0.4em 0; }

    /* Tables */
    .regimen-session-content table {
        width: 100%;
        margin: 1.5em 0;
        border-collapse: collapse;
        font-size: 0.9375rem;
    }
    .regimen-session-content thead {
        background: var(--ui-muted-5);
        border-bottom: 2px solid var(--ui-border);
    }
    .regimen-session-content th {
        padding: 0.625em 1em;
        text-align: left;
        font-weight: 600;
        color: var(--ui-primary);
    }
    .regimen-session-content td {
        padding: 0.625em 1em;
        border-top: 1px solid var(--ui-border);
    }

    /* Links */
    .regimen-session-content a {
        color: #2563eb;
        text-decoration: underline;
        text-underline-offset: 2px;
    }
    .regimen-session-content a:hover { color: #1d4ed8; }
    .dark .regimen-session-content a { color: #60a5fa; }
    .dark .regimen-session-content a:hover { color: #93c5fd; }

    /* Blockquotes (echte, nicht Callouts) */
    .regimen-session-content blockquote {
        margin: 1.5em 0;
        padding: 0.5em 1em;
        border-left: 3px solid var(--ui-muted-10);
        color: var(--ui-muted);
        font-style: italic;
    }

    /* Horizontale Trenner */
    .regimen-session-content hr {
        margin: 2.5em 0;
        border: none;
        border-top: 1px solid var(--ui-border);
    }

    /* === Code-Blocks === */
    .regimen-session-content pre {
        margin: 1.25em 0;
        background: #0d1117;
        border-radius: 0.5rem;
        padding: 1rem 1.25rem;
        overflow-x: auto;
        font-size: 0.875rem;
        line-height: 1.6;
    }
    .regimen-session-content pre code {
        background: transparent;
        padding: 0;
        color: #c9d1d9;
        font-size: inherit;
    }
    .regimen-session-content :not(pre) > code {
        background: var(--ui-muted-10);
        padding: 0.125rem 0.375rem;
        border-radius: 0.25rem;
        font-size: 0.875em;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }

    /* === Interaktive Applets (Sandbox-iframe) === */
    .regimen-session-content .regimen-applet-wrap {
        margin: 1.5em 0;
        border: 1px solid var(--ui-border);
        border-radius: 0.9rem;
        overflow: hidden;
        background: var(--ui-surface);
    }
    .regimen-session-content .regimen-applet-bar {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.9rem;
        font-family: var(--ui-font-mono);
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: var(--ui-primary);
        background: var(--ui-primary-5);
        border-bottom: 1px solid var(--ui-border);
    }
    .regimen-session-content .regimen-applet-dot {
        width: 7px;
        height: 7px;
        border-radius: 9999px;
        background: var(--ui-primary);
        box-shadow: 0 0 0 3px var(--ui-primary-20);
    }
    .regimen-session-content .regimen-applet {
        width: 100%;
        display: block;
        border: 0;
        height: 160px; /* Startwert — per postMessage angepasst */
        background: transparent;
    }
</style>

{{-- Highlight.js init: einmal beim Laden + bei jeder Livewire-Navigation --}}
<script>
    (function() {
        function initRegimenHighlight() {
            if (typeof hljs !== 'undefined' && typeof hljs.highlightAll === 'function') {
                hljs.highlightAll();
            }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initRegimenHighlight);
        } else {
            // already loaded — re-init (e.g. via wire:navigate)
            setTimeout(initRegimenHighlight, 50);
        }
        document.addEventListener('livewire:navigated', initRegimenHighlight);
    })();
</script>

{{-- Applet-iframes melden ihre Höhe per postMessage — Listener einmalig registrieren --}}
<script>
    (function() {
        if (window.__regimenAppletResize) return;
        window.__regimenAppletResize = true;
        window.addEventListener('message', function(e) {
            var d = e.data;
            if (!d || d.__regimenApplet !== true || typeof d.height !== 'number') return;
            var frames = document.querySelectorAll('iframe.regimen-applet');
            for (var i = 0; i < frames.length; i++) {
                if (frames[i].contentWindow === e.source) {
                    frames[i].style.height = Math.max(48, d.height) + 'px';
                    break;
                }
            }
        });
    })();
</script>

<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$session->title" icon="heroicon-o-document-text" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Regimen', 'href' => route('regimen.dashboard'), 'icon' => 'academic-cap'],
            ['label' => 'Bibliothek', 'href' => route('regimen.topics.index')],
            ['label' => $session->topic->title, 'href' => route('regimen.topics.show', ['uuid' => $session->topic->uuid])],
            ['label' => $session->title, 'href' => route('regimen.sessions.show', ['uuid' => $session->uuid])],
        ]" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="{{ $session->topic->title }}" icon="heroicon-o-list-bullet" width="w-72" :defaultOpen="true">
            <nav class="p-3 space-y-1">
                @foreach($topicSessions as $i => $tl)
                    <a wire:navigate href="{{ route('regimen.sessions.show', ['uuid' => $tl->uuid]) }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm
                              {{ $tl->id === $session->id
                                 ? 'bg-[var(--ui-primary-5)] text-[var(--ui-primary)] font-medium'
                                 : 'text-gray-700 dark:text-gray-300 hover:bg-[var(--ui-muted-5)]' }}">
                        <span class="flex-shrink-0 w-5 h-5 rounded-full text-[10px] flex items-center justify-center
                                     {{ isset($completedSet[$tl->id]) ? 'bg-emerald-500 text-white' : 'bg-[var(--ui-muted-10)] text-gray-500' }}">
                            @if(isset($completedSet[$tl->id]))
                                @svg('heroicon-s-check', 'w-3 h-3')
                            @else
                                {{ $i + 1 }}
                            @endif
                        </span>
                        <span class="flex-1 truncate">{{ $tl->title }}</span>
                    </a>
                @endforeach
            </nav>
        </x-ui-page-sidebar>
    </x-slot>

    @php
        $pos = $topicSessions->search(fn ($l) => $l->id === $session->id);
        $num = $pos === false ? null : $pos + 1;
        $count = $topicSessions->count();
    @endphp

    <x-ui-page-container>

        {{-- ===== HERO ===== --}}
        <div class="relative overflow-hidden rounded-3xl border border-[var(--ui-border)]"
             style="background-image: linear-gradient(135deg, color-mix(in srgb, {{ $accentColor }} 14%, transparent), transparent 62%);">
            <div class="p-8 md:p-10 max-w-3xl">
                <div class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.14em]" style="font-family: var(--ui-font-mono);">
                    <a wire:navigate href="{{ route('regimen.topics.show', ['uuid' => $session->topic->uuid]) }}" class="hover:underline" style="color: {{ $accentColor }};">{{ $session->topic->title }}</a>
                    @if($num)
                        <span class="text-gray-300">·</span>
                        <span class="text-gray-400">Lektion {{ $num }} / {{ $count }}</span>
                    @endif
                </div>
                <h1 class="mt-3 text-3xl md:text-[2.4rem] leading-[1.1] font-bold tracking-tight text-gray-900 dark:text-gray-100" style="font-family: var(--ui-font-mono); text-wrap: balance;">{{ $session->title }}</h1>
                @if($session->summary)
                    <p class="mt-3 text-[15px] md:text-lg leading-relaxed text-gray-600 dark:text-gray-300">{{ $session->summary }}</p>
                @endif
                <div class="mt-4 flex items-center gap-4 text-xs" style="font-family: var(--ui-font-mono);">
                    @if($session->estimated_minutes)
                        <span class="inline-flex items-center gap-1.5 text-gray-500 dark:text-gray-400">@svg('heroicon-o-clock', 'w-4 h-4') ~{{ $session->estimated_minutes }} min</span>
                    @endif
                    @if($isCompleted)
                        <span class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-semibold">@svg('heroicon-s-check-circle', 'w-4 h-4') Abgeschlossen</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===== CONTENT: Reader (füllt) + sichtbares Panel rechts ===== --}}
        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_320px] gap-8 xl:gap-12 items-start">

            {{-- Reader + Prev/Next --}}
            <div class="min-w-0 space-y-10">
                <article class="regimen-session-content">
                    {!! $renderedContent !!}
                </article>

                {{-- ===== CONCEPT-CHECK (Quiz) ===== --}}
                @if($hasQuiz)
                    @php $pq = $quizResult['per_question'] ?? []; @endphp
                    <section id="concept-check" class="scroll-mt-6 rounded-3xl border border-[var(--ui-border)] bg-[var(--ui-surface)] overflow-hidden">
                        <div class="px-6 py-5 border-b border-[var(--ui-border)] bg-[var(--ui-muted-5)]">
                            <div class="flex items-center gap-2 text-gray-900 dark:text-gray-100 font-bold" style="font-family: var(--ui-font-mono);">
                                @svg('heroicon-o-academic-cap', 'w-5 h-5 text-[var(--ui-primary)]')
                                Concept-Check
                            </div>
                            <p class="mt-1 text-[13px] text-gray-500 dark:text-gray-400">
                                Beantworte die Fragen, um diese Lektion abzuschließen. Bestehensgrenze <span style="font-family: var(--ui-font-mono);">{{ $quiz->passThreshold() }}%</span>.
                            </p>
                        </div>

                        @if($isCompleted && !$quizResult)
                            {{-- Bereits bestanden (frühere Sitzung) --}}
                            <div class="p-6">
                                <div class="flex items-center gap-3 rounded-2xl border border-emerald-500/25 bg-emerald-500/[0.07] p-4">
                                    @svg('heroicon-s-check-badge', 'w-7 h-7 text-emerald-500 flex-shrink-0')
                                    <div>
                                        <div class="font-semibold text-emerald-700 dark:text-emerald-300">Concept-Check bestanden</div>
                                        <div class="text-[13px] text-gray-500 dark:text-gray-400">Diese Lektion ist abgeschlossen. Du kannst sie rechts wieder öffnen, um erneut zu üben.</div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="p-6 space-y-7">
                                @foreach($quizQuestions as $qi => $q)
                                    @php $fb = $pq[$q['id']] ?? null; @endphp
                                    <div class="space-y-3">
                                        <div class="flex items-start gap-2.5">
                                            <span class="flex-shrink-0 mt-0.5 w-6 h-6 rounded-full bg-[var(--ui-muted-10)] text-gray-600 dark:text-gray-300 text-xs font-semibold flex items-center justify-center" style="font-family: var(--ui-font-mono);">{{ $qi + 1 }}</span>
                                            <div class="min-w-0 flex-1 text-gray-900 dark:text-gray-100 font-medium [&>p]:m-0 [&_code]:text-[0.9em]">{!! $q['prompt_html'] !!}</div>
                                            @if($q['is_multiple'])
                                                <span class="flex-shrink-0 text-[10px] uppercase tracking-wide text-gray-400 border border-[var(--ui-border)] rounded px-1.5 py-0.5" style="font-family: var(--ui-font-mono);">Mehrfach</span>
                                            @endif
                                        </div>

                                        <div class="space-y-2" style="padding-left: 2.15rem;">
                                            @foreach($q['options'] as $opt)
                                                @php
                                                    $isCorrectOpt = $fb && in_array($opt['id'], $fb['correct_ids'] ?? [], true);
                                                    $wasSelected  = $fb && in_array($opt['id'], $fb['selected'] ?? [], true);
                                                    $rowClass = 'border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)]';
                                                    if ($fb) {
                                                        if ($isCorrectOpt) $rowClass = 'border-emerald-500/40 bg-emerald-500/[0.08]';
                                                        elseif ($wasSelected) $rowClass = 'border-red-500/40 bg-red-500/[0.07]';
                                                        else $rowClass = 'border-[var(--ui-border)] opacity-70';
                                                    }
                                                @endphp
                                                <label class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl border cursor-pointer transition {{ $rowClass }} {{ $fb ? 'cursor-default' : '' }}">
                                                    <input
                                                        type="{{ $q['is_multiple'] ? 'checkbox' : 'radio' }}"
                                                        @if($q['is_multiple'])
                                                            wire:model="quizAnswers.{{ $q['id'] }}"
                                                            value="{{ $opt['id'] }}"
                                                        @else
                                                            wire:model="quizAnswers.{{ $q['id'] }}"
                                                            value="{{ $opt['id'] }}"
                                                        @endif
                                                        @disabled($fb !== null)
                                                        class="flex-shrink-0 text-[var(--ui-primary)] focus:ring-[var(--ui-primary)] {{ $q['is_multiple'] ? 'rounded' : 'rounded-full' }}"
                                                    >
                                                    <span class="flex-1 text-sm text-gray-800 dark:text-gray-200">{{ $opt['label'] }}</span>
                                                    @if($fb && $isCorrectOpt)
                                                        @svg('heroicon-s-check-circle', 'w-5 h-5 text-emerald-500 flex-shrink-0')
                                                    @elseif($fb && $wasSelected)
                                                        @svg('heroicon-s-x-circle', 'w-5 h-5 text-red-500 flex-shrink-0')
                                                    @endif
                                                </label>
                                            @endforeach
                                        </div>

                                        @if($fb && $q['explanation_html'])
                                            <div class="ml-8 rounded-xl bg-[var(--ui-muted-5)] border border-[var(--ui-border)] px-4 py-3 text-[13px] text-gray-600 dark:text-gray-300 [&>p]:m-0">
                                                <span class="font-semibold text-gray-700 dark:text-gray-200">Warum: </span>{!! $q['explanation_html'] !!}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach

                                {{-- Footer: Auswerten / Ergebnis --}}
                                @if(!$quizResult)
                                    <div class="pt-2">
                                        <button wire:click="submitQuiz" wire:loading.attr="disabled"
                                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[var(--ui-primary)] text-white text-sm font-semibold hover:opacity-90 transition disabled:opacity-60"
                                                style="box-shadow: 0 6px 16px -6px rgba(79,70,229,.7);">
                                            @svg('heroicon-o-check-circle', 'w-4 h-4') Auswerten
                                        </button>
                                    </div>
                                @else
                                    @php $passed = $quizResult['passed'] ?? false; @endphp
                                    <div class="pt-2 rounded-2xl border p-4 {{ $passed ? 'border-emerald-500/30 bg-emerald-500/[0.07]' : 'border-amber-500/30 bg-amber-500/[0.07]' }}">
                                        <div class="flex items-center gap-3">
                                            @if($passed)
                                                @svg('heroicon-s-check-badge', 'w-8 h-8 text-emerald-500 flex-shrink-0')
                                            @else
                                                @svg('heroicon-s-arrow-plan', 'w-8 h-8 text-amber-500 flex-shrink-0')
                                            @endif
                                            <div class="flex-1">
                                                <div class="font-semibold {{ $passed ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">
                                                    {{ $passed ? 'Bestanden — Lektion abgeschlossen!' : 'Noch nicht bestanden' }}
                                                </div>
                                                <div class="text-[13px] text-gray-600 dark:text-gray-400">
                                                    {{ $quizResult['correct'] }} von {{ $quizResult['total'] }} richtig
                                                    (<span style="font-family: var(--ui-font-mono);">{{ $quizResult['score_pct'] }}%</span>,
                                                    nötig {{ $quiz->passThreshold() }}%).
                                                </div>
                                            </div>
                                            @if(!$passed)
                                                <button wire:click="retryQuiz"
                                                        class="flex-shrink-0 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[var(--ui-primary)] text-white text-sm font-semibold hover:opacity-90 transition">
                                                    @svg('heroicon-o-arrow-plan', 'w-4 h-4') Nochmal
                                                </button>
                                            @elseif($next)
                                                <a wire:navigate href="{{ route('regimen.sessions.show', ['uuid' => $next->uuid]) }}"
                                                   class="flex-shrink-0 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[var(--ui-primary)] text-white text-sm font-semibold hover:opacity-90 transition">
                                                    Weiter @svg('heroicon-s-arrow-right', 'w-4 h-4')
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </section>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-[var(--ui-border)]">
                    @if($prev)
                        <a wire:navigate href="{{ route('regimen.sessions.show', ['uuid' => $prev->uuid]) }}"
                           class="group flex items-center gap-3 p-4 mt-6 rounded-2xl border border-[var(--ui-border)] bg-[var(--ui-surface)] hover:bg-[var(--ui-muted-5)] hover:-translate-y-0.5 transition-all">
                            @svg('heroicon-o-arrow-left', 'w-5 h-5 text-gray-400 flex-shrink-0')
                            <div class="min-w-0">
                                <div class="text-[10px] uppercase tracking-wider text-gray-400" style="font-family: var(--ui-font-mono);">Vorherige</div>
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $prev->title }}</div>
                            </div>
                        </a>
                    @else
                        <div class="hidden sm:block"></div>
                    @endif

                    @if($next)
                        <a wire:navigate href="{{ route('regimen.sessions.show', ['uuid' => $next->uuid]) }}"
                           class="group flex items-center justify-end gap-3 p-4 sm:mt-6 rounded-2xl border hover:-translate-y-0.5 transition-all text-right {{ $nextIsNewChapter ? 'border-[var(--ui-primary-20)] bg-[var(--ui-primary-5)] hover:bg-[var(--ui-primary-10)]' : 'border-[var(--ui-border)] bg-[var(--ui-surface)] hover:bg-[var(--ui-muted-5)]' }}">
                            <div class="min-w-0">
                                <div class="text-[10px] uppercase tracking-wider {{ $nextIsNewChapter ? 'text-[var(--ui-primary)] font-semibold' : 'text-gray-400' }}" style="font-family: var(--ui-font-mono);">{{ $nextIsNewChapter ? 'Nächstes Kapitel · ' . $nextChapterTitle : 'Nächste' }}</div>
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $next->title }}</div>
                            </div>
                            @svg('heroicon-o-arrow-right', 'w-5 h-5 text-gray-400 flex-shrink-0')
                        </a>
                    @elseif($primaryPlan)
                        <a wire:navigate href="{{ route('regimen.plans.show', ['uuid' => $primaryPlan->uuid]) }}"
                           class="group flex items-center justify-end gap-3 p-4 sm:mt-6 rounded-2xl border border-emerald-500/25 bg-emerald-500/[0.07] hover:-translate-y-0.5 transition-all text-right">
                            <div class="min-w-0">
                                <div class="text-[10px] uppercase tracking-wider text-emerald-600 dark:text-emerald-400 font-semibold" style="font-family: var(--ui-font-mono);">Kurs-Ende</div>
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">Zurück zur Kursübersicht</div>
                            </div>
                            @svg('heroicon-o-flag', 'w-5 h-5 text-emerald-500 flex-shrink-0')
                        </a>
                    @endif
                </div>
            </div>

            {{-- Sichtbares Panel rechts (ersetzt die einklappbare Sidebar) --}}
            <aside class="lg:sticky lg:top-4 space-y-4">

                {{-- Aktion / Status --}}
                @if($isCompleted)
                    <div class="rounded-2xl border border-emerald-500/25 bg-emerald-500/[0.07] p-5 space-y-3">
                        <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-semibold">
                            @svg('heroicon-s-check-circle', 'w-6 h-6') Abgeschlossen
                        </div>
                        @if($next)
                            <a wire:navigate href="{{ route('regimen.sessions.show', ['uuid' => $next->uuid]) }}"
                               class="flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-xl bg-[var(--ui-primary)] text-white text-sm font-semibold hover:opacity-90 transition">
                                {{ $nextIsNewChapter ? 'Nächstes Kapitel' : 'Nächste Lektion' }} @svg('heroicon-s-arrow-right', 'w-4 h-4')
                            </a>
                        @elseif($primaryPlan)
                            <a wire:navigate href="{{ route('regimen.plans.show', ['uuid' => $primaryPlan->uuid]) }}"
                               class="flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-xl bg-[var(--ui-primary)] text-white text-sm font-semibold hover:opacity-90 transition">
                                @svg('heroicon-s-flag', 'w-4 h-4') Zur Kursübersicht
                            </a>
                        @endif
                        <button wire:click="reopen" class="w-full text-center text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">Wieder als offen markieren</button>
                    </div>
                @elseif($hasQuiz)
                    {{-- Abschluss ist an den Concept-Check gebunden --}}
                    <div class="rounded-2xl border border-[var(--ui-primary-20)] bg-[var(--ui-primary-5)] p-5 space-y-3">
                        <div class="flex items-start gap-2.5">
                            @svg('heroicon-o-academic-cap', 'w-5 h-5 text-[var(--ui-primary)] flex-shrink-0 mt-0.5')
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-gray-100">Concept-Check offen</div>
                                <div class="text-[13px] text-gray-500 dark:text-gray-400">Bestehe den Check unten, um diese Lektion abzuschließen ({{ $quiz->passThreshold() }}% nötig).</div>
                            </div>
                        </div>
                        <a href="#concept-check"
                           class="flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-xl bg-[var(--ui-primary)] text-white text-sm font-semibold hover:opacity-90 transition"
                           style="box-shadow: 0 6px 16px -6px rgba(79,70,229,.7);">
                            @svg('heroicon-o-arrow-down', 'w-4 h-4') Zum Concept-Check
                        </a>
                    </div>
                @else
                    <div class="rounded-2xl border border-[var(--ui-border)] bg-[var(--ui-muted-5)] p-5 space-y-3">
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">Fertig mit der Lektion?</div>
                            <div class="text-[13px] text-gray-500 dark:text-gray-400">Markier sie als erledigt, um deinen Fortschritt zu tracken.</div>
                        </div>
                        <button wire:click="markComplete"
                                class="flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-xl bg-[var(--ui-primary)] text-white text-sm font-semibold hover:opacity-90 transition"
                                style="box-shadow: 0 6px 16px -6px rgba(79,70,229,.7);">
                            @svg('heroicon-o-check', 'w-4 h-4') Als erledigt markieren
                        </button>
                        @if($next)
                            <a wire:navigate href="{{ route('regimen.sessions.show', ['uuid' => $next->uuid]) }}"
                               class="flex items-center justify-center gap-2 w-full px-4 py-2 rounded-xl border border-[var(--ui-border)] bg-[var(--ui-surface)] text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-[var(--ui-muted-5)] transition">
                                {{ $nextIsNewChapter ? 'Nächstes Kapitel' : 'Nächste Lektion' }} @svg('heroicon-o-arrow-right', 'w-4 h-4')
                            </a>
                        @endif
                    </div>
                @endif

                {{-- Meta --}}
                <div class="rounded-2xl border border-[var(--ui-border)] bg-[var(--ui-surface)] p-5 space-y-3">
                    <a wire:navigate href="{{ route('regimen.topics.show', ['uuid' => $session->topic->uuid]) }}"
                       class="flex items-center gap-2.5 group">
                        <span class="w-8 h-8 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)] flex items-center justify-center flex-shrink-0" style="color: {{ $accentColor }};">
                            @svg($session->topic->icon ?: 'heroicon-o-folder', 'w-4 h-4')
                        </span>
                        <div class="min-w-0">
                            <div class="text-[10px] uppercase tracking-wider text-gray-400" style="font-family: var(--ui-font-mono);">Thema</div>
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate group-hover:text-[var(--ui-primary)] transition">{{ $session->topic->title }}</div>
                        </div>
                    </a>

                    @if($session->estimated_minutes)
                        <div class="h-px bg-[var(--ui-border)]"></div>
                        <div class="flex items-center justify-between text-[13px]">
                            <span class="text-gray-500 dark:text-gray-400">Dauer</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100" style="font-family: var(--ui-font-mono);">~{{ $session->estimated_minutes }} min</span>
                        </div>
                    @endif
                    @if($num)
                        <div class="flex items-center justify-between text-[13px]">
                            <span class="text-gray-500 dark:text-gray-400">Position</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100" style="font-family: var(--ui-font-mono);">{{ $num }} / {{ $count }}</span>
                        </div>
                    @endif
                </div>

                {{-- Kurse, in denen diese Lektion vorkommt --}}
                @if($planMemberships->isNotEmpty())
                    <div class="rounded-2xl border border-[var(--ui-border)] bg-[var(--ui-surface)] p-5">
                        <div class="text-[10px] uppercase tracking-wider text-gray-400 mb-3" style="font-family: var(--ui-font-mono);">Teil dieser Kurse</div>
                        <div class="space-y-1.5">
                            @foreach($planMemberships as $p)
                                <a wire:navigate href="{{ route('regimen.plans.show', ['uuid' => $p->uuid]) }}"
                                   class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 hover:text-[var(--ui-primary)] transition">
                                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background: {{ $p->coverColor() }};"></span>
                                    <span class="truncate">{{ $p->title }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </aside>
        </div>
    </x-ui-page-container>
</x-ui-page>
</div>
