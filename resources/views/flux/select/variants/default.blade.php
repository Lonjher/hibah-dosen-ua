@blaze

@props([
    'name' => $attributes->whereStartsWith('wire:model')->first(),
    'placeholder' => null,
    'invalid' => null,
    'size' => null,
])

@php
$invalid ??= ($name && $errors->has($name));

$classes = Flux::classes()
    ->add('appearance-none') // Strip native <select> styles
    ->add('[:where(&)]:w-full ps-3 pe-8 sm:ps-2 sm:pe-7 block')
    ->add(match ($size) {
        default => 'h-9 sm:h-7 py-1.5 sm:py-1 text-[13px] sm:text-xs leading-[1rem] rounded-full',
        'sm'    => 'h-8 sm:h-6 py-1 sm:py-0.5 text-[12px] sm:text-[11px] leading-[0.875rem] rounded-full',
        'xs'    => 'h-7 sm:h-5 py-0.5 sm:py-0 text-[11px] sm:text-[10px] leading-[0.75rem] rounded-full',
        'lg'    => 'h-10 sm:h-8 py-2 sm:py-1.5 text-[14px] sm:text-sm leading-[1.125rem] rounded-full',
        'xl'    => 'h-12 sm:h-10 py-3 sm:py-2.5 text-base leading-[1.375rem] rounded-2xl',
    })
    // ── Glass surface ──
    ->add('bg-white/50 dark:bg-zinc-900/40 backdrop-blur-xl backdrop-saturate-200')
    ->add('dark:disabled:bg-white/[7%] backdrop-blur-sm')
    ->add('text-zinc-700 dark:text-zinc-200 disabled:text-zinc-500 dark:disabled:text-zinc-400')
    // ── Border + inset highlight ──
    ->add('border border-white/60 dark:border-white/10')
    ->add('ring-1 ring-inset ring-white/40 dark:ring-white/5')
    ->add('shadow-sm shadow-slate-900/5 dark:shadow-black/30')
    // ── Hover ──
    ->add('hover:bg-white/70 dark:hover:bg-zinc-900/50')
    ->add('hover:border-white/80 dark:hover:border-white/15')
    // ── Focus ──
    ->add('focus:outline-none focus:bg-white/80 dark:focus:bg-zinc-900/60')
    ->add('focus:ring-2 focus:ring-emerald-500/25 focus:border-emerald-400/60')
    ->add('focus:shadow-md focus:shadow-emerald-500/20')
    // ── Transition ──
    ->add('transition-all duration-200 ease-out')
    // Placeholder styling
    ->add('has-[option.placeholder:checked]:text-zinc-400 dark:has-[option.placeholder:checked]:text-zinc-500')
    // Windows option fallback
    ->add('dark:[&_option]:bg-zinc-700 dark:[&_option]:text-white')
    ->add('disabled:shadow-none disabled:cursor-not-allowed')
    ->add($invalid
        ? 'border-rose-500/60 ring-rose-500/30 focus:ring-rose-500/40 focus:border-rose-500/60'
        : null
    )
    ;
@endphp

<select
    {{ $attributes->class($classes) }}
    @if ($invalid) aria-invalid="true" data-invalid @endif
    @isset ($name) name="{{ $name }}" @endisset
    @if (is_numeric($size)) size="{{ $size }}" @endif
    data-flux-control
    data-flux-select-native
    data-flux-group-target
>
    <?php if ($placeholder): ?>
        <option value="" disabled selected class="placeholder">{{ $placeholder }}</option>
    <?php endif; ?>

    {{ $slot }}
</select>
