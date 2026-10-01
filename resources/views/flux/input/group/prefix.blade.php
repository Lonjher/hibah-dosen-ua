@blaze(fold: true)

@props([
    'size' => null,
])

@php
$classes = Flux::classes([
    'flex items-center px-2.5 whitespace-nowrap leading-none',
    'text-zinc-800 dark:text-zinc-200',
    'bg-zinc-800/5 dark:bg-white/20',
    'border-zinc-200 dark:border-white/10',
    'border-s border-t border-b shadow-xs',
])->add(match ($size) {
    default => 'text-xs h-7 rounded-s-md',
    'sm'    => 'text-[11px] h-6 rounded-s-md',
    'xs'    => 'text-[10px] h-5 rounded-s',
    'lg'    => 'text-sm h-8 rounded-s-md',
    'xl'    => 'text-base h-10 rounded-s-lg',
});
@endphp

<div {{ $attributes->class($classes) }} data-flux-input-group-prefix>
    {{ $slot }}
</div>
