@blaze(fold: true)

@php
    $classes = Flux::classes()
        // ── Ukuran: fit konten ──
        ->add('!w-max !min-w-0')
        ->add('!max-w-[min(18rem,calc(100vw-1rem))]')

        // ── Reset native popover ──
        ->add('m-0 p-1.5')

        // ── Visual glass ──
        ->add('rounded-2xl')
        ->add('border border-white/50 dark:border-white/10')
        ->add('bg-white/60 dark:bg-zinc-900/60')
        ->add('backdrop-blur-2xl backdrop-saturate-150')
        ->add('shadow-xl shadow-slate-900/10 dark:shadow-black/50')
        ->add('ring-1 ring-black/5 dark:ring-white/5')

        // ── Tipografi & Overflow ──
        ->add('text-[11px] leading-tight')
        ->add('overflow-hidden')
        ->add('focus:outline-hidden');
@endphp

<ui-menu {{ $attributes->class($classes) }} popover="manual" data-flux-menu>
    {{ $slot }}
</ui-menu>
