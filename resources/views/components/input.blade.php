@props([
    'name'        => null,
    'id'          => null,
    'type'        => 'text',
    'size'        => 'md',      // sm | md | lg
    'rounded'     => 'full',    // full | lg | md
    'placeholder' => null,
    'label'       => null,
    'required'    => false,
    'hint'        => null,
    'icon'        => null,      // optional leading icon
    'value'       => null,
])

@php
    $inputId = $id ?? $name ?? 'input-' . \Illuminate\Support\Str::random(6);

    // ---- Size presets ----
    $sizes = [
        'sm' => [
            'height'   => 'py-1',
            'padding'  => 'px-2.5',
            'text'     => 'text-[10px]',
            'icon'     => 'size-3',
            'iconLeft' => 'left-2',
            'paddingL' => 'pl-7',
        ],
        'md' => [
            'height'   => 'py-1.5',
            'padding'  => 'px-2.5',
            'text'     => 'text-[11px]',
            'icon'     => 'size-3.5',
            'iconLeft' => 'left-2.5',
            'paddingL' => 'pl-8',
        ],
        'lg' => [
            'height'   => 'py-2',
            'padding'  => 'px-3',
            'text'     => 'text-[12px]',
            'icon'     => 'size-4',
            'iconLeft' => 'left-3',
            'paddingL' => 'pl-9',
        ],
    ];
    $s = $sizes[$size] ?? $sizes['md'];

    // ---- Rounded presets ----
    $roundeds = [
        'full' => 'rounded-full',
        'lg'   => 'rounded-lg',
        'md'   => 'rounded-md',
    ];
    $radius = $roundeds[$rounded] ?? $roundeds['md'];

    // ---- File input: skip normal class entirely ----
    $isFile = $type === 'file';

    // ---- Layout: col kalau ada label ----
    $layoutClass = $label ? 'flex flex-col gap-1' : '';

    // ---- Input class ----
    $inputClass = $isFile
        ? trim("
            block w-full text-[11px] px-2.5 py-1.5 {$radius}
            file:mr-2.5 file:py-1 file:px-2.5 file:rounded-md file:border-0
            file:text-[10.5px] file:font-medium
            file:bg-violet-100 file:text-violet-700
            dark:file:bg-violet-900/40 dark:file:text-violet-300
            hover:file:bg-violet-200 dark:hover:file:bg-violet-900/60

            bg-white dark:bg-zinc-800
            text-slate-900 dark:text-zinc-100

            border border-slate-200 dark:border-zinc-700

            shadow-sm shadow-slate-200/60 dark:shadow-zinc-950/40
            hover:shadow-sm hover:shadow-violet-500/20 dark:hover:shadow-violet-500/20
            hover:border-slate-300 dark:hover:border-zinc-600

            focus:outline-none
            focus:border-violet-500 dark:focus:border-violet-500
            focus:ring-2 focus:ring-violet-500/25 dark:focus:ring-violet-500/30

            transition-all duration-200
        ")
        : trim("
            block w-full {$s['padding']} {$s['height']} {$radius}
            " . ($icon ? "{$s['paddingL']}" : '') . "
            bg-white dark:bg-zinc-800
            text-slate-900 dark:text-zinc-100
            placeholder:text-slate-400 dark:placeholder:text-zinc-500
            {$s['text']} font-body

            border border-slate-200 dark:border-zinc-700

            shadow-sm shadow-slate-200/60 dark:shadow-zinc-950/40
            hover:shadow-sm hover:shadow-emerald-500/20 dark:hover:shadow-emerald-500/20
            hover:border-slate-300 dark:hover:border-zinc-600

            focus:outline-none
            focus:bg-white dark:focus:bg-zinc-900
            focus:border-emerald-500 dark:focus:border-emerald-500
            focus:ring-2 focus:ring-emerald-500/25 dark:focus:ring-emerald-500/30
            focus:shadow-md focus:shadow-emerald-500/10

            transition-all duration-200
        ");
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full group ' . $layoutClass]) }}>

    @if ($label)
        <label for="{{ $inputId }}"
            class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300">
            {{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif

    <div class="relative w-full">
        @if ($icon && ! $isFile)
            <flux:icon :name="$icon"
                class="{{ $s['icon'] }} absolute {{ $s['iconLeft'] }} top-1/2 -translate-y-1/2
                       text-slate-400 dark:text-zinc-500
                       pointer-events-none
                       group-focus-within:text-emerald-500 dark:group-focus-within:text-emerald-400
                       transition-colors" />
        @endif

        <input
            type="{{ $type }}"
            id="{{ $inputId }}"
            @if ($name) name="{{ $name }}" @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($value !== null) value="{{ $value }}" @endif
            {{ $attributes->except('class')->merge(['class' => $inputClass]) }} />
    </div>

    @if ($hint)
        <p class="mt-0.5 text-[9.5px] text-slate-400 dark:text-zinc-500">{{ $hint }}</p>
    @endif
</div>
