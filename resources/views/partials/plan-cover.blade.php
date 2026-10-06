@php
    /**
     * Typografischer Plan-Cover: Plan-Code auf kategorie-getoentem Verlauf.
     * Erwartet: $plan. Optional: $size ('card' | 'rail' | 'hero').
     */
    $size = $size ?? 'card';
    $coverColor = $plan->coverColor();
    $code = $plan->code ?: strtoupper(\Illuminate\Support\Str::substr($plan->slug, 0, 6));
    $prefix = $plan->category?->code_prefix ?: (string) \Illuminate\Support\Str::of($code)->before('-');
    $levelLabel = $plan->levelLabel();

    $box = match ($size) {
        'hero' => 'h-44 px-7 py-6',
        'rail' => 'h-[74px] px-4',
        default => 'h-32 px-4 py-3',
    };
    $codeSize = $size === 'hero' ? 'text-sm' : ($size === 'rail' ? 'text-[13px]' : 'text-xs');
    $wmSize = match ($size) {
        'hero' => 'text-[150px] -bottom-8 -right-2',
        'rail' => 'text-[64px] -bottom-5 -right-1',
        default => 'text-[92px] -bottom-7 -right-2',
    };
@endphp

<div class="relative overflow-hidden flex flex-col {{ $size === 'rail' ? 'justify-center' : ($size === 'hero' ? 'justify-end' : 'justify-between') }} {{ $box }}"
     style="background-image: linear-gradient(135deg, {{ $coverColor }}, color-mix(in srgb, {{ $coverColor }} 82%, #ffffff));">

    {{-- feine diagonale Linien-Textur --}}
    <div class="pointer-events-none absolute inset-0"
         style="background-image: repeating-linear-gradient(115deg, rgba(255,255,255,.07) 0 1px, transparent 1px 22px);"></div>

    {{-- grosses Code-Wasserzeichen --}}
    <div class="pointer-events-none absolute {{ $wmSize }} font-bold leading-none tracking-tight select-none"
         style="font-family: var(--ui-font-mono); color: rgba(255,255,255,.16);">{{ $prefix }}</div>

    <div class="relative z-10 flex items-start justify-between gap-2">
        <span class="{{ $codeSize }} font-bold tracking-wider text-white/95"
              style="font-family: var(--ui-font-mono);">{{ $code }}</span>

        @if($levelLabel && $size !== 'rail')
            <span class="text-[10px] font-semibold uppercase tracking-wider px-2 py-1 rounded-full bg-white/90 text-gray-900"
                  style="font-family: var(--ui-font-mono);">{{ $levelLabel }}</span>
        @endif
    </div>

    @if($size === 'hero')
        <div class="relative z-10 mt-3">
            <div class="text-xs font-bold uppercase tracking-[0.12em] text-white/85" style="font-family: var(--ui-font-mono);">
                {{ $code }}@if($plan->category) · {{ strtoupper($plan->category->title) }}@endif@if($levelLabel) · {{ strtoupper($levelLabel) }}@endif
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-white" style="font-family: var(--ui-font-mono);">{{ $plan->title }}</h1>
        </div>
    @endif
</div>
