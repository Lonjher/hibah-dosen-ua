@blaze(fold: true)

@php
    $classes = Flux::classes()
        ->add('[:where(&)]:min-w-38 p-2 m-0')
        ->add('rounded-2xl')
        ->add('border border-white/60 dark:border-zinc-700/60')
        ->add('bg-white/75 dark:bg-zinc-800/75')
        ->add('backdrop-blur-2xl backdrop-saturate-150')
        ->add('shadow-xl shadow-slate-900/10 dark:shadow-black/50')
        ->add('ring-1 ring-black/5 dark:ring-white/5')
        ->add('text-[12px] leading-tight')
        ->add('focus:outline-hidden');
@endphp

<ui-menu {{ $attributes->class($classes) }} popover="manual" data-flux-menu>
    {{ $slot }}
</ui-menu>
