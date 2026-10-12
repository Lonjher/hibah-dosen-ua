@blaze(fold: true)

@props([
    'label',
])

@php
$classes = Flux::classes()
    // ── Optgroup: hanya bisa styling font & warna ──
    ->add('bg-white dark:bg-zinc-800')
    ->add('text-slate-500 dark:text-zinc-400')
    ->add('text-[10px] font-semibold uppercase tracking-[0.08em]')
    ->add('italic')
    ;
@endphp

<optgroup
    {{ $attributes->class($classes) }}
    label="{{ $label }}"
>{{ $slot }}</optgroup>
