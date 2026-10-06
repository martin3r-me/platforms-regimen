<div class="h-full">
<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Zertifikat" icon="heroicon-o-document-check" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Regimen', 'href' => route('regimen.dashboard'), 'icon' => 'bolt'],
            ['label' => 'Pläne', 'href' => route('regimen.plans.index')],
            ['label' => $plan?->code ?: $plan?->title, 'href' => $plan ? route('regimen.plans.show', ['uuid' => $plan->uuid]) : '#'],
            ['label' => 'Zertifikat', 'href' => route('regimen.certificates.show', ['uuid' => $certificate->uuid])],
        ]" />
    </x-slot>

    @php
        $code = $plan?->code ?: 'CERT';
        $prefix = (string) \Illuminate\Support\Str::of($code)->before('-');
        $issued = $certificate->issued_at ?? $certificate->created_at;
    @endphp

    <x-ui-page-container>
        <div class="max-w-3xl mx-auto py-4 md:py-8">

            {{-- ===== ZERTIFIKAT ===== --}}
            <div class="relative overflow-hidden rounded-3xl border border-[var(--ui-border)] bg-[var(--ui-surface)] shadow-xl">
                {{-- Akzent-Rahmen oben --}}
                <div class="h-2 w-full" style="background-image: linear-gradient(90deg, color-mix(in srgb, {{ $accentColor }} 90%, #ffffff), color-mix(in srgb, {{ $accentColor }} 55%, #000000));"></div>

                {{-- Watermark-Code --}}
                <div class="pointer-events-none absolute -right-8 -bottom-20 font-bold leading-none select-none"
                     style="font-family: var(--ui-font-mono); font-size: 240px; color: color-mix(in srgb, {{ $accentColor }} 7%, transparent); letter-spacing: -.05em;">{{ $prefix }}</div>

                <div class="relative z-10 px-8 py-10 md:px-14 md:py-14 text-center">

                    {{-- Kopf --}}
                    <div class="flex items-center justify-center gap-2 text-[11px] font-bold uppercase tracking-[0.2em] text-gray-400" style="font-family: var(--ui-font-mono);">
                        @svg('heroicon-o-bolt', 'w-4 h-4') BHG.DIGITAL Regimen
                    </div>

                    <div class="mt-6 text-sm font-medium uppercase tracking-[0.15em] text-gray-500 dark:text-gray-400" style="font-family: var(--ui-font-mono);">Abschlusszertifikat</div>

                    {{-- Siegel --}}
                    <div class="mt-6 flex justify-center">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center shadow-lg"
                             style="background-image: linear-gradient(135deg, color-mix(in srgb, {{ $accentColor }} 92%, #ffffff), color-mix(in srgb, {{ $accentColor }} 60%, #000000));">
                            @svg('heroicon-s-check-badge', 'w-9 h-9 text-white')
                        </div>
                    </div>

                    {{-- Inhaber --}}
                    <p class="mt-8 text-[13px] text-gray-500 dark:text-gray-400">Hiermit wird bestätigt, dass</p>
                    <h1 class="mt-2 text-3xl md:text-4xl font-bold tracking-tight text-gray-900 dark:text-gray-100" style="font-family: var(--ui-font-mono); text-wrap: balance;">{{ $holder?->name ?? 'Teilnehmer' }}</h1>

                    <p class="mt-5 text-[13px] text-gray-500 dark:text-gray-400">den Plan erfolgreich abgeschlossen hat</p>

                    {{-- Plan --}}
                    <div class="mt-3 inline-flex flex-col items-center gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-bold tracking-wider px-2.5 py-1 rounded-full text-white" style="font-family: var(--ui-font-mono); background: {{ $accentColor }};">{{ $code }}</span>
                            @if($plan?->levelLabel())
                                <span class="text-[11px] font-semibold uppercase tracking-wider px-2.5 py-1 rounded-full bg-[var(--ui-muted-10)] text-gray-600 dark:text-gray-300" style="font-family: var(--ui-font-mono);">{{ $plan->levelLabel() }}</span>
                            @endif
                        </div>
                        <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100 max-w-xl" style="font-family: var(--ui-font-mono); text-wrap: balance;">{{ $plan?->title ?? 'Plan' }}</h2>
                    </div>

                    {{-- Fußzeile: Serial + Datum --}}
                    <div class="mt-10 pt-6 border-t border-[var(--ui-border)] flex flex-col sm:flex-row items-center justify-between gap-4 text-left">
                        <div>
                            <div class="text-[10px] uppercase tracking-wider text-gray-400" style="font-family: var(--ui-font-mono);">Seriennummer</div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100" style="font-family: var(--ui-font-mono);">{{ $certificate->serial }}</div>
                        </div>
                        <div class="sm:text-right">
                            <div class="text-[10px] uppercase tracking-wider text-gray-400" style="font-family: var(--ui-font-mono);">Ausgestellt am</div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100" style="font-family: var(--ui-font-mono);">{{ $issued?->format('d.m.Y') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Aktionen --}}
            <div class="mt-6 flex items-center justify-center gap-3">
                @if($plan)
                    <a wire:navigate href="{{ route('regimen.plans.show', ['uuid' => $plan->uuid]) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-[var(--ui-border)] bg-[var(--ui-surface)] text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-[var(--ui-muted-5)] transition">
                        @svg('heroicon-o-arrow-left', 'w-4 h-4') Zum Plan
                    </a>
                @endif
                <button onclick="window.print()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-[var(--ui-border)] bg-[var(--ui-surface)] text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-[var(--ui-muted-5)] transition">
                    @svg('heroicon-o-printer', 'w-4 h-4') Drucken / PDF
                </button>
            </div>
        </div>
    </x-ui-page-container>
</x-ui-page>
</div>
