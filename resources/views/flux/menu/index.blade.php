@blaze(fold: true)

@php
    $classes = Flux::classes()
        // ── Ukuran: fit konten, jangan ada minimum ──
        ->add('!w-max !min-w-0')
        ->add('!max-w-[min(18rem,calc(100vw-1rem))]')

        // ── Reset native popover default ──
        ->add('m-0 p-1')

        // ── Visual ──
        ->add('rounded-lg')
        ->add('border border-white/60 dark:border-zinc-700/60')
        ->add('bg-white/80 dark:bg-zinc-800/80')
        ->add('backdrop-blur-2xl backdrop-saturate-150')
        ->add('shadow-lg shadow-slate-900/10 dark:shadow-black/50')
        ->add('ring-1 ring-black/5 dark:ring-white/5')

        // ── Tipografi & Overflow ──
        ->add('text-[11px] leading-tight')
        ->add('overflow-hidden')
        ->add('focus:outline-hidden');
@endphp

<ui-menu {{ $attributes->class($classes) }} popover="manual" data-flux-menu>
    {{ $slot }}
</ui-menu>
