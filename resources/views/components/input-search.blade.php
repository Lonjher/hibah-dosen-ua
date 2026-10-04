@props([
    'placeholder' => 'Search...',
    'name'        => null,
    'id'          => null,
    'value'       => null,
    'maxWidth'    => 'max-w-xs',
    'size'        => 'md',        // sm | md | lg
    'autofocus'   => false,
    'rounded'     => 'full',      // full | lg | md
    'responsive'  => true,        // true = hidden on mobile, false = always visible
])

@php
    $inputId = $id ?? $name ?? 'search-' . Str::random(6);

    // ---- Size presets ----
    $sizes = [
        'sm' => [
            'height'   => 'py-1',
            'padding'  => 'pl-7 pr-2.5',
            'text'     => 'text-[10px]',
            'icon'     => 'size-3',
            'iconLeft' => 'left-2',
        ],
        'md' => [
            'height'   => 'py-1.5',
            'padding'  => 'pl-8 pr-3',
            'text'     => 'text-[11px]',
            'icon'     => 'size-3.5',
            'iconLeft' => 'left-2',
        ],
        'lg' => [
            'height'   => 'py-2.5',
            'padding'  => 'pl-10 pr-4',
            'text'     => 'text-sm',
            'icon'     => 'size-4',
            'iconLeft' => 'left-3.5',
        ],
    ];

    $s = $sizes[$size] ?? $sizes['md'];

    // ---- Rounded presets ----
    $roundeds = [
        'full' => 'rounded-full',
        'lg'   => 'rounded-lg',
        'md'   => 'rounded-md',
    ];
    $radius = $roundeds[$rounded] ?? $roundeds['full'];

    // ---- Wrapper display ----
    $display = $responsive
        ? 'hidden sm:flex items-center'
        : 'flex items-center';

    // ---- Kumpulkan class input ----
    $inputClass = trim("
        w-full {$s['padding']} {$s['height']} {$radius}
        bg-white dark:bg-zinc-800
        text-slate-900 dark:text-zinc-100
        placeholder:text-slate-400 dark:placeholder:text-zinc-500
        {$s['text']} font-body
        border border-slate-200 dark:border-zinc-700

        shadow-sm shadow-slate-200/60 dark:shadow-zinc-950/40
        hover:shadow-md hover:shadow-slate-200/70 dark:hover:shadow-zinc-950/50
        hover:border-slate-300 dark:hover:border-zinc-600

        focus:outline-none
        focus:bg-white dark:focus:bg-zinc-900
        focus:border-emerald-500 dark:focus:border-emerald-500
        focus:ring-2 focus:ring-emerald-500/25 dark:focus:ring-emerald-500/30
        focus:shadow-md focus:shadow-emerald-500/10

        transition-all duration-200
    ");
@endphp

<div {{ $attributes->only('class')->merge(['class' => "relative w-full {$maxWidth} {$display}"]) }}>

    {{-- Icon kiri --}}
    <flux:icon.magnifying-glass
        class="{{ $s['icon'] }} absolute {{ $s['iconLeft'] }}
               text-slate-400 dark:text-zinc-500
               pointer-events-none
               peer-focus:text-emerald-500 dark:peer-focus:text-emerald-400
               transition-colors" />

    {{-- Input --}}
    <input
        type="text"
        id="{{ $inputId }}"
        @if ($name) name="{{ $name }}" @endif
        @if ($value !== null) value="{{ $value }}" @endif
        @if ($autofocus) autofocus @endif
        placeholder="{{ $placeholder }}"
        {{ $attributes->except('class')->merge(['class' => $inputClass]) }} />
</div>
