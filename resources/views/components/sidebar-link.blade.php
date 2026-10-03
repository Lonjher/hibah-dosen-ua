@props([
    'href'   => '',
    'title'  => '',
    'icon'   => 'squares-2x2',
    'badge'  => null,
])

@php
    $isActive = request()->routeIs($href);
@endphp

<a
    href="{{ route($href) }}"
    wire:navigate
    @class([
        'group relative flex items-center gap-2.5 lg:gap-2
         px-3 py-2.5 lg:px-2.5 lg:py-1.5
         rounded-full lg:rounded-full
         text-[13px] lg:text-[11px] leading-tight
         transition-all duration-200 ease-out
         hover:translate-x-0.5 hover:shadow-sm
         active:scale-[0.98]',

        // ── ACTIVE ──
        'bg-emerald-50 text-emerald-700 font-semibold
         shadow-[inset_0_0_0_1px_rgb(16_185_129_/_0.15)]
         dark:bg-emerald-900/25 dark:text-emerald-300
         dark:shadow-[inset_0_0_0_1px_rgb(52_211_153_/_0.2)]'
            => $isActive,

        // ── INACTIVE ──
        'text-slate-600 font-medium
         hover:bg-slate-100 hover:text-slate-900
         dark:text-zinc-400 dark:hover:bg-zinc-800/60 dark:hover:text-zinc-100'
            => ! $isActive,
    ])
>
    {{-- Left indicator bar (only when active) --}}
    @if ($isActive)
        <span class="absolute left-0 top-1/2 -translate-y-1/2
                     w-0.5 h-5 lg:h-4 rounded-r-full"></span>
    @endif

    {{-- Left indicator preview on hover (inactive only) --}}
    @if (! $isActive)
        <span class="pointer-events-none absolute left-0 top-1/2 -translate-y-1/2
                     w-0.5 h-5 lg:h-4 rounded-r-full
                     opacity-0 scale-y-50
                     group-hover:opacity-100 group-hover:scale-y-100
                     transition-all duration-200 ease-out
                     origin-center"></span>
    @endif

    {{-- Icon --}}
    <flux:icon :name="$icon"
        @class([
            'size-[18px] lg:size-3.5 shrink-0
             transition-all duration-200 ease-out',

            // Active
            'text-emerald-600 dark:text-emerald-400' => $isActive,

            // Inactive + hover
            'text-slate-400
             group-hover:text-emerald-500 group-hover:scale-110 group-hover:-rotate-3
             dark:text-zinc-500 dark:group-hover:text-emerald-400' => ! $isActive,
        ])
    />

    {{-- Title --}}
    <span class="truncate flex-1 transition-transform duration-200 ease-out
                 group-hover:translate-x-px">
        {{ $title }}
    </span>

    {{-- Badge --}}
    @if ($badge)
        <span @class([
            'shrink-0 inline-flex items-center justify-center
             min-w-[20px] lg:min-w-[16px] h-5 lg:h-4 px-1.5 lg:px-1 rounded-full
             text-[10px] lg:text-[9px] font-bold
             transition-all duration-200 ease-out
             group-hover:scale-105',
            'bg-emerald-500 text-white
             group-hover:bg-emerald-600' => $isActive,
            'bg-slate-200 text-slate-600
             group-hover:bg-emerald-100 group-hover:text-emerald-700
             dark:bg-zinc-700 dark:text-zinc-300
             dark:group-hover:bg-emerald-900/40 dark:group-hover:text-emerald-300' => ! $isActive,
        ])>
            {{ $badge }}
        </span>
    @endif
</a>
