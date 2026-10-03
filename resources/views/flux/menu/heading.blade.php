@blaze(fold: true)

@php
$classes = Flux::classes([
    'px-2 pt-1.5 pb-0.5 w-full',
    'flex items-center',
    'text-start text-[9px] font-semibold uppercase tracking-wider',
    'text-zinc-500 dark:text-zinc-400',
]);
@endphp

<div {{ $attributes->class($classes) }} data-flux-menu-heading>
    <div class="w-5 hidden [[data-flux-menu]:has(>[data-flux-menu-item-has-icon])_&]:block"></div>

    <div>{{ $slot }}</div>
</div>
