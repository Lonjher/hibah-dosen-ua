@props([
    'name'        => null,
    'id'          => null,
    'rows'        => 3,
    'rounded'     => 'full',
    'color'       => 'sky',
    'placeholder' => null,
    'label'       => null,
    'required'    => false,
    'hint'        => null,
])

@php
    $textareaId = $id ?? $name ?? 'textarea-' . \Illuminate\Support\Str::random(6);

    $roundeds = ['full' => 'rounded-full', 'lg' => 'rounded-lg', 'md' => 'rounded-md', 'sm' => 'rounded-sm'];
    $radius = $roundeds[$rounded] ?? $roundeds['md'];

    $colors = [
        'sky'     => 'focus:ring-sky-500/25 focus:border-sky-500 dark:focus:border-sky-500 dark:focus:ring-sky-500/30',
        'emerald' => 'focus:ring-emerald-500/25 focus:border-emerald-500 dark:focus:border-emerald-500 dark:focus:ring-emerald-500/30',
        'violet'  => 'focus:ring-violet-500/25 focus:border-violet-500 dark:focus:border-violet-500 dark:focus:ring-violet-500/30',
        'rose'    => 'focus:ring-rose-500/25 focus:border-rose-500 dark:focus:border-rose-500 dark:focus:ring-rose-500/30',
        'amber'   => 'focus:ring-amber-500/25 focus:border-amber-500 dark:focus:border-amber-500 dark:focus:ring-amber-500/30',
        'indigo'  => 'focus:ring-indigo-500/25 focus:border-indigo-500 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/30',
        'blue'    => 'focus:ring-blue-500/25 focus:border-blue-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/30',
        'pink'    => 'focus:ring-pink-500/25 focus:border-pink-500 dark:focus:border-pink-500 dark:focus:ring-pink-500/30',
        'fuchsia' => 'focus:ring-fuchsia-500/25 focus:border-fuchsia-500 dark:focus:border-fuchsia-500 dark:focus:ring-fuchsia-500/30',
    ];
    $accent = $colors[$color] ?? $colors['sky'];

    // ---- Layout: col kalau ada label ----
    $layoutClass = $label ? 'flex flex-col gap-1' : '';

    $textareaClass = trim("
        block w-full {$radius} text-[11.5px] px-2.5 py-1.5
        border border-slate-200 dark:border-zinc-700
        bg-white dark:bg-zinc-800
        text-slate-900 dark:text-zinc-100
        placeholder:text-slate-400 dark:placeholder:text-zinc-500

        shadow-sm shadow-slate-200/60 dark:shadow-zinc-950/40
        hover:shadow-sm hover:shadow-emerald-500/20 dark:hover:shadow-emerald-500/20
        hover:border-slate-300 dark:hover:border-zinc-600

        focus:outline-none
        focus:ring-2 {$accent}
        focus:shadow-md focus:shadow-emerald-500/10

        resize-none transition-all duration-200
    ");
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full group ' . $layoutClass]) }}>
    @if ($label)
        <label for="{{ $textareaId }}"
            class="block text-[11px] font-medium text-slate-700 dark:text-zinc-300">
            {{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif

    <textarea
        id="{{ $textareaId }}"
        @if ($name) name="{{ $name }}" @endif
        rows="{{ $rows }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        {{ $attributes->except('class')->merge(['class' => $textareaClass]) }}></textarea>

    @if ($hint)
        <p class="mt-0.5 text-[9.5px] text-slate-400 dark:text-zinc-500">{{ $hint }}</p>
    @endif
</div>
