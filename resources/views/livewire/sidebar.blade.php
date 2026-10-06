<div>
    <div x-show="!collapsed" class="p-3 text-sm italic text-[var(--ui-secondary)] uppercase border-b border-[var(--ui-border)] mb-2">
        Regimen
    </div>

    <x-ui-sidebar-list label="Navigation">
        <x-ui-sidebar-item :href="route('regimen.dashboard')" :active="request()->routeIs('regimen.dashboard')">
            @svg('heroicon-o-home', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('regimen.plans.index')" :active="request()->routeIs('regimen.plans.index')">
            @svg('heroicon-o-rectangle-stack', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Pläne</span>
            <span class="ml-1.5 text-[10px] text-[var(--ui-muted)]">geführt</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('regimen.topics.index')" :active="request()->routeIs('regimen.topics.*')">
            @svg('heroicon-o-book-open', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Bibliothek</span>
            <span class="ml-1.5 text-[10px] text-[var(--ui-muted)]">frei</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    @if($assignments->isNotEmpty())
        <x-ui-sidebar-list label="Meine Pflichtpläne">
            @foreach($assignments as $a)
                <x-ui-sidebar-item :href="route('regimen.plans.show', ['uuid' => $a['uuid']])" :active="request()->is('*/regimen/plans/' . $a['uuid'])">
                    @svg('heroicon-o-flag', 'w-4 h-4 ' . ($a['overdue'] ? 'text-red-500' : 'text-[var(--ui-primary)]'))
                    <span class="ml-2 text-sm truncate">{{ $a['title'] }}</span>
                    <x-slot name="trailing">
                        @if($a['due'])
                            <span class="text-[10px] font-semibold {{ $a['overdue'] ? 'text-red-500' : 'text-[var(--ui-muted)]' }}" title="fällig {{ $a['due_full'] }}">{{ $a['due'] }}</span>
                        @else
                            <span class="text-[10px] font-semibold text-[var(--ui-primary)]">Pflicht</span>
                        @endif
                    </x-slot>
                </x-ui-sidebar-item>
            @endforeach
        </x-ui-sidebar-list>
    @endif

    @if($plans->isNotEmpty())
        <x-ui-sidebar-list label="Meine Pläne">
            @foreach($plans as $plan)
                <x-ui-sidebar-item :href="route('regimen.plans.show', ['uuid' => $plan['uuid']])" :active="request()->is('*/regimen/plans/' . $plan['uuid'])">
                    @svg($plan['icon'] ?: 'heroicon-o-rectangle-stack', 'w-4 h-4 text-[var(--ui-secondary)]')
                    <span class="ml-2 text-sm truncate">{{ $plan['title'] }}</span>
                    <x-slot name="trailing">
                        @if($plan['completed'])
                            @svg('heroicon-s-check-circle', 'w-4 h-4 text-emerald-500')
                        @else
                            <span class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">{{ $plan['pct'] }}%</span>
                        @endif
                    </x-slot>
                </x-ui-sidebar-item>
            @endforeach
        </x-ui-sidebar-list>
    @endif

    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('regimen.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] {{ request()->routeIs('regimen.dashboard') ? 'bg-[var(--ui-primary-5)] text-[var(--ui-primary)]' : '' }}">
                @svg('heroicon-o-home', 'w-5 h-5')
            </a>
            <a href="{{ route('regimen.plans.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] {{ request()->routeIs('regimen.plans.*') ? 'bg-[var(--ui-primary-5)] text-[var(--ui-primary)]' : '' }}">
                @svg('heroicon-o-rectangle-stack', 'w-5 h-5')
            </a>
            <a href="{{ route('regimen.topics.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] {{ request()->routeIs('regimen.topics.*') ? 'bg-[var(--ui-primary-5)] text-[var(--ui-primary)]' : '' }}">
                @svg('heroicon-o-book-open', 'w-5 h-5')
            </a>
        </div>
    </div>
</div>
