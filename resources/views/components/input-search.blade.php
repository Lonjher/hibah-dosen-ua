@props([
    'placeholder' => 'Cari...',
    'name' => null,
    'id' => null,
    'value' => null,
    'maxWidth' => 'max-w-xs',
    'size' => 'md',        // sm | md | lg
    'autofocus' => false,
    'rounded' => 'full',   // full | lg | md
])

@php
    $inputId = $id ?? $name ?? 'search-' . Str::random(6);

    // ---- Size presets ----
    $sizes = [
        'sm' => [
            'height'    => 'py-1',
            'padding'   => 'pl-7 pr-2.5',
            'text'      => 'text-[10px]',
            'icon'      => 'size-3',
            'iconLeft'  => 'left-2',
        ],
        'md' => [
            'height'    => 'py-1.5',
            'padding'   => 'pl-8 pr-3',
            'text'      => 'text-[11px]',
            'icon'      => 'size-3.5',
            'iconLeft'  => 'left-2',
        ],
        'lg' => [
            'height'    => 'py-2.5',
            'padding'   => 'pl-10 pr-4',
            'text'      => 'text-sm',
            'icon'      => 'size-4',
            'iconLeft'  => 'left-3.5',
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

    // ---- Kumpulkan class input ----
    $inputClass = trim("
        w-full {$s['padding']} {$s['height']} {$radius}
        bg-surface-container-low/70 text-on-surface
        placeholder:text-outline {$s['text']} font-body
        border border-white shadow-xs
        focus:bg-surface-container-lowest focus:outline-none
        focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500
        transition-all duration-200
        dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder-zinc-500
        dark:border-zinc-700 dark:focus:bg-zinc-900 dark:focus:border-emerald-700
        dark:focus:ring-emerald-500/30
    ");
@endphp

<div {{ $attributes->only('class')->merge(['class' => "relative w-full {$maxWidth} hidden sm:flex items-center"]) }}>
    {{-- Icon kiri --}}
    <flux:icon.magnifying-glass
        class="{{ $s['icon'] }} absolute {{ $s['iconLeft'] }} text-outline dark:text-zinc-500 pointer-events-none" />

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
