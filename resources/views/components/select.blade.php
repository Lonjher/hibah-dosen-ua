@props([
    'name'        => null,
    'id'          => null,
    'size'        => 'md',
    'rounded'     => 'full',
    'responsive'  => false,
    'maxWidth'    => null,
    'placeholder' => null,
    'color'       => 'sky',
    'label'       => null,
    'required'    => false,
])

@php
    $selectId = $id ?? $name ?? 'select-' . Str::random(6);

    // ---- Size presets ----
    $sizes = [
        'sm' => [
            'height'  => 'py-0.5',
            'padding' => 'pl-2 pr-6',
            'text'    => 'text-[10px]',
            'chevron' => 'size-2.5 right-1.5',
        ],
        'md' => [
            'height'  => 'py-1',
            'padding' => 'pl-2 pr-6',
            'text'    => 'text-[10.5px]',
            'chevron' => 'size-3 right-2',
        ],
        'lg' => [
            'height'  => 'py-1.5',
            'padding' => 'pl-2.5 pr-7',
            'text'    => 'text-[11.5px]',
            'chevron' => 'size-3.5 right-2.5',
        ],
    ];
    $s = $sizes[$size] ?? $sizes['md'];

    // ---- Rounded presets ----
    $roundeds = [
        'full' => 'rounded-full',
        'lg'   => 'rounded-lg',
        'md'   => 'rounded-md',
        'sm'   => 'rounded-sm',
    ];
    $radius = $roundeds[$rounded] ?? $roundeds['md'];

    // ---- Color presets (focus ring & border) ----
    $colors = [
        'sky'     => 'focus:ring-sky-500/25 focus:border-sky-500 dark:focus:border-sky-500 dark:focus:ring-sky-500/30',
        'emerald' => 'focus:ring-emerald-500/25 focus:border-emerald-500 dark:focus:border-emerald-500 dark:focus:ring-emerald-500/30',
        'violet'  => 'focus:ring-violet-500/25 focus:border-violet-500 dark:focus:border-violet-500 dark:focus:ring-violet-500/30',
        'rose'    => 'focus:ring-rose-500/25 focus:border-rose-500 dark:focus:border-rose-500 dark:focus:ring-rose-500/30',
        'amber'   => 'focus:ring-amber-500/25 focus:border-amber-500 dark:focus:border-amber-500 dark:focus:ring-amber-500/30',
        'indigo'  => 'focus:ring-indigo-500/25 focus:border-indigo-500 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/30',
        'blue'    => 'focus:ring-blue-500/25 focus:border-blue-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/30',
        'pink'    => 'focus:ring-pink-500/25 focus:border-pink-500 dark:focus:border-pink-500 dark:focus:ring-pink-500/30',
    ];
    $focusColor = $colors[$color] ?? $colors['sky'];

    // ---- Wrapper visibility ----
    $display = $responsive ? 'hidden sm:flex' : 'flex';

    // ---- Wrapper width ----
    $width = $maxWidth ?? 'w-auto';

    // ---- Layout: col kalau ada label, kalau tidak apa adanya ----
    $layoutClass = $label ? 'flex-col gap-1' : '';

    // ---- Select class ----
    $selectClass = trim("
        block w-full {$s['padding']} {$s['height']} {$radius}
        bg-white dark:bg-zinc-800
        text-slate-700 dark:text-zinc-200
        {$s['text']} font-body

        border border-slate-200 dark:border-zinc-700

        shadow-sm shadow-slate-200/60 dark:shadow-zinc-950/40
        hover:shadow-sm hover:shadow-emerald-500/20 dark:hover:shadow-emerald-500/20
        hover:border-slate-300 dark:hover:border-zinc-600

        focus:outline-none
        focus:ring-2 {$focusColor}
        focus:shadow-md

        appearance-none cursor-pointer
        transition-all duration-200
    ");
@endphp

<div {{ $attributes->only('class')->merge(['class' => "{$display} {$layoutClass} {$width} group"]) }}>
    @if ($label)
        <label for="{{ $selectId }}"
            class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300">
            {{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif

    <div class="relative group w-full">
        {{-- Select --}}
        <select
            id="{{ $selectId }}"
            @if ($name) name="{{ $name }}" @endif
            {{ $attributes->except('class')->merge(['class' => $selectClass]) }}>

            @if ($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif

            {{ $slot }}
        </select>

        {{-- Chevron icon (kanan) --}}
        <flux:icon.chevron-down
            class="{{ $s['chevron'] }} absolute top-1/2 -translate-y-1/2
                   text-slate-400 dark:text-zinc-500
                   pointer-events-none
                   group-focus-within:text-{{ $color }}-500 dark:group-focus-within:text-{{ $color }}-400
                   group-focus-within:rotate-180
                   transition-all duration-200" />
    </div>
</div>
