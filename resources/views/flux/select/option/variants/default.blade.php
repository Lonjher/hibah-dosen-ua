@blaze(fold: true)

@props([
    'value' => null,
])

@php
$classes = Flux::classes()
    // ── Background & text ──
    ->add('bg-white dark:bg-zinc-800')
    ->add('text-slate-700 dark:text-zinc-100')
    ->add('text-[13px] sm:text-[11px] font-medium')
    // ── Padding: nyaman di mobile ──
    ->add('py-2.5 sm:py-1.5 px-4 sm:px-3')
    // ── Selected ──
    ->add('checked:bg-emerald-500/15 checked:text-emerald-700 checked:font-semibold')
    ->add('dark:checked:bg-emerald-500/20 dark:checked:text-emerald-200')
    // ── Hover (Firefox & Linux Chrome) ──
    ->add('hover:bg-emerald-500/10 hover:text-emerald-700')
    ->add('dark:hover:bg-emerald-500/15 dark:hover:text-emerald-300')
    // ── Disabled ──
    ->add('disabled:opacity-50 disabled:text-zinc-400 disabled:italic')
    ->add('dark:disabled:text-zinc-500')
    ;
@endphp

<option
    {{ $attributes->class($classes) }}
    @if (isset($value)) value="{{ $value }}" @endif
    @if (isset($value)) wire:key="{{ $value }}" @endif
>{{ $slot }}</option>
