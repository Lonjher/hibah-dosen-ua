@props([
'href' => '',
'title' => '',
'icon' => 'squares-2x2',
])

@php
$isActive = request()->routeIs($href);
@endphp

<a
href="{{ route($href) }}"
wire:navigate
@class([
'group flex items-center gap-2 px-2 py-1.5 rounded-lg border-l-2 font-title-sm text-[11px] transition-all',
    // Active
    'bg-emerald-500/10 text-emerald-600 border-emerald-500 font-semibold dark:text-emerald-300'
        => $isActive,
    // Inactive
    'text-on-surface-variant border-transparent font-medium hover:text-on-surface hover:bg-surface-container-low/50 dark:text-zinc-400 dark:hover:text-zinc-100 dark:hover:bg-zinc-800/50'
        => !$isActive,
])
>
<flux:icon :name="$icon"
    @class([
        'size-4 shrink-0 transition-colors',
        'text-emerald-500' => $isActive,
        'text-outline group-hover:text-emerald-500' => !$isActive,
    ])
/>
<span>{{ $title }}</span>
</a>
