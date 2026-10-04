@blaze(fold: true, unsafe: ['icon:trailing', 'icon:leading', 'icon:variant'])

@php $iconTrailing ??= $attributes->pluck('icon:trailing'); @endphp
@php $iconLeading ??= $attributes->pluck('icon:leading'); @endphp
@php $iconVariant ??= $attributes->pluck('icon:variant'); @endphp

@props([
    'iconTrailing' => null,
    'variant' => 'outline',
    'iconVariant' => null,
    'iconLeading' => null,
    'type' => 'button',
    'loading' => null,
    'align' => 'center',
    'size' => 'base',
    'square' => null,
    'color' => null,
    'inset' => null,
    'icon' => null,
    'kbd' => null,
])

@php
$iconLeading = $icon ??= $iconLeading;

// Button should be a square if it has no text contents...
$square ??= $slot->isEmpty();

// Size-up icons in square/icon-only buttons...
$iconVariant ??= ($size === 'xs')
    ? ($square ? 'micro' : 'micro')
    : ($square ? 'mini' : 'micro');

$iconTrailingVariant ??= $attributes->pluck('icon-trailing:variant', $iconVariant);

// When using the outline icon variant, we need to size it down to match the default icon sizes...
$iconClasses = Flux::classes()
    ->add($iconVariant === 'outline' ? ($square && $size !== 'xs' ? 'size-4' : 'size-3.5') : '')
    ->add($attributes->pluck('icon:class'))
    ;

$iconTrailingClasses = Flux::classes()
    ->add($iconTrailingVariant === 'outline' ? ($square && $size !== 'xs' ? 'size-4' : 'size-3.5') : '')
    ->add($attributes->pluck('icon-trailing:class'))
    ;

$isTypeSubmitAndNotDisabledOnRender = $type === 'submit' && ! $attributes->has('disabled');

$isJsMethod = str_starts_with($attributes->whereStartsWith('wire:click')->first() ?? '', '$js.');

$loading ??= $loading ?? ($isTypeSubmitAndNotDisabledOnRender || $attributes->whereStartsWith('wire:click')->isNotEmpty() && ! $isJsMethod);

if ($loading && $type !== 'submit' && ! $isJsMethod) {
    $attributes = $attributes->merge(['wire:loading.attr' => 'data-flux-loading']);

    if (! $attributes->has('wire:target') && $target = $attributes->whereStartsWith('wire:click')->first()) {
        $attributes = $attributes->merge(['wire:target' => $target], escape: false);
    }
}

$classes = Flux::classes()
    ->add('relative items-center font-medium justify-center whitespace-nowrap')
    ->add('inline-flex')

    // ── Base transition ──
    ->add('transition-all duration-150 ease-out')
    ->add('hover:scale-[1.02] active:scale-[0.97]')

    // ── Disabled state ──
    ->add('disabled:opacity-50 dark:disabled:opacity-40 disabled:cursor-default disabled:pointer-events-none disabled:shadow-none')

    // ── Align ──
    ->add(match ($align) {
        'start' => 'justify-start',
        'center' => 'justify-center',
        'end' => 'justify-end',
    })

    // ── Size (rounded-full, compact) ──
    // Match referensi: px-3 py-1.5 text-[11px] rounded-full
    ->add(match ($size) {
        'base' => 'h-7 text-[11px] rounded-full gap-1.5' . ' ' . (
            $square
                ? 'w-7'
                : ($iconLeading && $iconLeading !== '' ? 'ps-2.5' : 'ps-3') . ' ' .
                  ($iconTrailing && $iconTrailing !== '' ? 'pe-2.5' : 'pe-3')
        ),
        'sm' => 'h-6 text-[10.5px] rounded-full gap-1' . ' ' . (
            $square
                ? 'w-6'
                : ($iconLeading && $iconLeading !== '' ? 'ps-2' : 'ps-2.5') . ' ' .
                  ($iconTrailing && $iconTrailing !== '' ? 'pe-2' : 'pe-2.5')
        ),
        'xs' => 'h-5 text-[10px] rounded-full gap-1' . ' ' . (
            $square
                ? 'w-5'
                : ($iconLeading && $iconLeading !== '' ? 'ps-1.5' : 'ps-2') . ' ' .
                  ($iconTrailing && $iconTrailing !== '' ? 'pe-1.5' : 'pe-2')
        ),
    })

    // ── Inset ──
    ->add($inset ? match ($size) {
        'base' => $square
            ? Flux::applyInset($inset, top: '-mt-2', right: '-me-2', bottom: '-mb-2', left: '-ms-2')
            : Flux::applyInset($inset, top: '-mt-2', right: ($iconTrailing && $iconTrailing !== '' ? '-me-2.5' : '-me-3'), bottom: '-mb-2', left: ($iconLeading && $iconLeading !== '' ? '-ms-2.5' : '-ms-3')),
        'sm' => $square
            ? Flux::applyInset($inset, top: '-mt-1.5', right: '-me-1.5', bottom: '-mb-1.5', left: '-ms-1.5')
            : Flux::applyInset($inset, top: '-mt-1.5', right: ($iconTrailing && $iconTrailing !== '' ? '-me-2' : '-me-2.5'), bottom: '-mb-1.5', left: ($iconLeading && $iconLeading !== '' ? '-ms-2' : '-ms-2.5')),
        'xs' => $square
            ? Flux::applyInset($inset, top: '-mt-1', right: '-me-1', bottom: '-mb-1', left: '-ms-1')
            : Flux::applyInset($inset, top: '-mt-1', right: ($iconTrailing && $iconTrailing !== '' ? '-me-1.5' : '-me-2'), bottom: '-mb-1', left: ($iconLeading && $iconLeading !== '' ? '-ms-1.5' : '-ms-2')),
    } : '')

    // ════════════════════════════════════════════════════════════
    //  BACKGROUND — muted / soft tints (tidak mencolok)
    // ════════════════════════════════════════════════════════════
    ->add(match ($variant) {
        'primary' => 'bg-emerald-600/85 hover:bg-emerald-600/95 dark:bg-emerald-600/80 dark:hover:bg-emerald-600/90',
        'filled'  => 'bg-zinc-100/80 hover:bg-zinc-100 dark:bg-zinc-800/70 dark:hover:bg-zinc-800',
        'outline' => 'bg-white/90 hover:bg-zinc-50 dark:bg-zinc-800/50 dark:hover:bg-zinc-800/70',
        'danger'  => 'bg-rose-600/85 hover:bg-rose-600/95 dark:bg-rose-600/80 dark:hover:bg-rose-600/90',
        'ghost'   => 'bg-transparent hover:bg-zinc-100/60 dark:hover:bg-zinc-800/50',
        'subtle'  => 'bg-transparent hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40',
    })

    // ════════════════════════════════════════════════════════════
    //  TEXT COLOR
    // ════════════════════════════════════════════════════════════
    ->add(match ($variant) {
        'primary' => 'text-white',
        'filled'  => 'text-zinc-700 hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-white',
        'outline' => 'text-zinc-700 hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-white',
        'danger'  => 'text-white',
        'ghost'   => 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white',
        'subtle'  => 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200',
    })

    // ════════════════════════════════════════════════════════════
    //  BORDER — halus, tidak hard
    // ════════════════════════════════════════════════════════════
    ->add(match ($variant) {
        'primary' => 'border border-emerald-700/15 dark:border-emerald-500/15',
        'outline' => 'border border-zinc-200/70 hover:border-zinc-300/70 dark:border-zinc-700/70 dark:hover:border-zinc-600/70',
        'filled'  => 'border border-zinc-200/50 dark:border-zinc-700/50',
        'danger'  => 'border border-rose-700/15 dark:border-rose-500/15',
        default   => '',
    })

    // ════════════════════════════════════════════════════════════
    //  SHADOW — halus, shadow-sm emerald 20%
    // ════════════════════════════════════════════════════════════
    ->add(match ($variant) {
        'primary' => 'shadow-sm shadow-emerald-500/20 hover:shadow-sm hover:shadow-emerald-500/30 dark:shadow-emerald-500/15 dark:hover:shadow-emerald-500/25',
        'danger'  => 'shadow-sm shadow-rose-500/20 hover:shadow-sm hover:shadow-rose-500/30 dark:shadow-rose-500/15 dark:hover:shadow-rose-500/25',
        'outline' => 'shadow-sm shadow-zinc-200/40 hover:shadow-sm hover:shadow-emerald-500/15 dark:shadow-zinc-950/30 dark:hover:shadow-emerald-500/10',
        'filled'  => 'shadow-sm shadow-zinc-200/30 hover:shadow-sm hover:shadow-emerald-500/15 dark:shadow-zinc-950/20 dark:hover:shadow-emerald-500/10',
        'ghost'   => 'shadow-none',
        'subtle'  => 'shadow-none',
    })

    // ════════════════════════════════════════════════════════════
    //  GROUPED BORDERS
    // ════════════════════════════════════════════════════════════
    ->add(match ($variant) {
        'ghost' => '',
        'subtle' => '',
        'outline' => '[[data-flux-button-group]_&]:border-s-0 [:is([data-flux-button-group]>&:first-child,_[data-flux-button-group]_:first-child>&)]:border-s-[1px]',
        'filled' => '[[data-flux-button-group]_&]:border-e [:is([data-flux-button-group]>&:last-child,_[data-flux-button-group]_:last-child>&)]:border-e-0 [[data-flux-button-group]_&]:border-zinc-200/80 dark:[[data-flux-button-group]_&]:border-zinc-900/50',
        'danger' => '[[data-flux-button-group]_&]:border-e [:is([data-flux-button-group]>&:last-child,_[data-flux-button-group]_:last-child>&)]:border-e-0 [[data-flux-button-group]_&]:border-rose-600 dark:[[data-flux-button-group]_&]:border-rose-900/25',
        'primary' => '[[data-flux-button-group]_&]:border-e-0 [:is([data-flux-button-group]>&:last-child,_[data-flux-button-group]_:last-child>&)]:border-e-[1px] dark:[:is([data-flux-button-group]>&:last-child,_[data-flux-button-group]_:last-child>&)]:border-e-0 dark:[:is([data-flux-button-group]>&:last-child,_[data-flux-button-group]_:last-child>&)]:border-s-[1px] [:is([data-flux-button-group]>&:not(:first-child),_[data-flux-button-group]_:not(:first-child)>&)]:border-s-[color-mix(in_srgb,var(--color-accent-foreground),transparent_85%)]',
    })

    // ════════════════════════════════════════════════════════════
    //  LOADING STATE
    // ════════════════════════════════════════════════════════════
    ->add($loading ? [
        '*:transition-opacity',
        $type === 'submit' ? '[&[disabled]>:not([data-flux-loading-indicator])]:opacity-0' : '[&[data-loading]>:not([data-flux-loading-indicator])]:opacity-0 [&[data-flux-loading]>:not([data-flux-loading-indicator])]:opacity-0',
        $type === 'submit' ? '[&[disabled]>[data-flux-loading-indicator]]:opacity-100' : '[&[data-loading]>[data-flux-loading-indicator]]:opacity-100 [&[data-flux-loading]>[data-flux-loading-indicator]]:opacity-100',
        $type === 'submit' ? '[&[disabled]]:pointer-events-none' : 'data-loading:pointer-events-none data-flux-loading:pointer-events-none',
    ] : [])

    // ════════════════════════════════════════════════════════════
    //  PRIMARY COLOR OVERRIDES (accent tokens)
    // ════════════════════════════════════════════════════════════
    ->add($variant === 'primary' ? match ($color) {
        'slate' => '[--color-accent:var(--color-slate-800)] [--color-accent-content:var(--color-slate-800)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-white)] dark:[--color-accent-content:var(--color-white)] dark:[--color-accent-foreground:var(--color-slate-800)]',
        'gray' => '[--color-accent:var(--color-gray-800)] [--color-accent-content:var(--color-gray-800)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-white)] dark:[--color-accent-content:var(--color-white)] dark:[--color-accent-foreground:var(--color-gray-800)]',
        'zinc' => '[--color-accent:var(--color-zinc-800)] [--color-accent-content:var(--color-zinc-800)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-white)] dark:[--color-accent-content:var(--color-white)] dark:[--color-accent-foreground:var(--color-zinc-800)]',
        'neutral' => '[--color-accent:var(--color-neutral-800)] [--color-accent-content:var(--color-neutral-800)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-white)] dark:[--color-accent-content:var(--color-white)] dark:[--color-accent-foreground:var(--color-neutral-800)]',
        'stone' => '[--color-accent:var(--color-stone-800)] [--color-accent-content:var(--color-stone-800)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-white)] dark:[--color-accent-content:var(--color-white)] dark:[--color-accent-foreground:var(--color-stone-800)]',
        'mauve' => '[--color-accent:var(--color-mauve-800)] [--color-accent-content:var(--color-mauve-800)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-white)] dark:[--color-accent-content:var(--color-white)] dark:[--color-accent-foreground:var(--color-mauve-800)]',
        'olive' => '[--color-accent:var(--color-olive-800)] [--color-accent-content:var(--color-olive-800)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-white)] dark:[--color-accent-content:var(--color-white)] dark:[--color-accent-foreground:var(--color-olive-800)]',
        'mist' => '[--color-accent:var(--color-mist-800)] [--color-accent-content:var(--color-mist-800)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-white)] dark:[--color-accent-content:var(--color-white)] dark:[--color-accent-foreground:var(--color-mist-800)]',
        'taupe' => '[--color-accent:var(--color-taupe-800)] [--color-accent-content:var(--color-taupe-800)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-white)] dark:[--color-accent-content:var(--color-white)] dark:[--color-accent-foreground:var(--color-taupe-800)]',
        'red' => '[--color-accent:var(--color-red-500)] [--color-accent-content:var(--color-red-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-red-500)] dark:[--color-accent-content:var(--color-red-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'orange' => '[--color-accent:var(--color-orange-500)] [--color-accent-content:var(--color-orange-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-orange-400)] dark:[--color-accent-content:var(--color-orange-400)] dark:[--color-accent-foreground:var(--color-orange-950)]',
        'amber' => '[--color-accent:var(--color-amber-400)] [--color-accent-content:var(--color-amber-600)] [--color-accent-foreground:var(--color-amber-950)] dark:[--color-accent:var(--color-amber-400)] dark:[--color-accent-content:var(--color-amber-400)] dark:[--color-accent-foreground:var(--color-amber-950)]',
        'yellow' => '[--color-accent:var(--color-yellow-400)] [--color-accent-content:var(--color-yellow-600)] [--color-accent-foreground:var(--color-yellow-950)] dark:[--color-accent:var(--color-yellow-400)] dark:[--color-accent-content:var(--color-yellow-400)] dark:[--color-accent-foreground:var(--color-yellow-950)]',
        'lime' => '[--color-accent:var(--color-lime-400)] [--color-accent-content:var(--color-lime-600)] [--color-accent-foreground:var(--color-lime-900)] dark:[--color-accent:var(--color-lime-400)] dark:[--color-accent-content:var(--color-lime-400)] dark:[--color-accent-foreground:var(--color-lime-950)]',
        'green' => '[--color-accent:var(--color-green-600)] [--color-accent-content:var(--color-green-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-green-600)] dark:[--color-accent-content:var(--color-green-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'emerald' => '[--color-accent:var(--color-emerald-600)] [--color-accent-content:var(--color-emerald-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-emerald-600)] dark:[--color-accent-content:var(--color-emerald-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'teal' => '[--color-accent:var(--color-teal-600)] [--color-accent-content:var(--color-teal-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-teal-600)] dark:[--color-accent-content:var(--color-teal-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'cyan' => '[--color-accent:var(--color-cyan-600)] [--color-accent-content:var(--color-cyan-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-cyan-600)] dark:[--color-accent-content:var(--color-cyan-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'sky' => '[--color-accent:var(--color-sky-600)] [--color-accent-content:var(--color-sky-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-sky-600)] dark:[--color-accent-content:var(--color-sky-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'blue' => '[--color-accent:var(--color-blue-500)] [--color-accent-content:var(--color-blue-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-blue-500)] dark:[--color-accent-content:var(--color-blue-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'indigo' => '[--color-accent:var(--color-indigo-500)] [--color-accent-content:var(--color-indigo-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-indigo-500)] dark:[--color-accent-content:var(--color-indigo-300)] dark:[--color-accent-foreground:var(--color-white)]',
        'violet' => '[--color-accent:var(--color-violet-500)] [--color-accent-content:var(--color-violet-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-violet-500)] dark:[--color-accent-content:var(--color-violet-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'purple' => '[--color-accent:var(--color-purple-500)] [--color-accent-content:var(--color-purple-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-purple-500)] dark:[--color-accent-content:var(--color-purple-300)] dark:[--color-accent-foreground:var(--color-white)]',
        'fuchsia' => '[--color-accent:var(--color-fuchsia-600)] [--color-accent-content:var(--color-fuchsia-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-fuchsia-600)] dark:[--color-accent-content:var(--color-fuchsia-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'pink' => '[--color-accent:var(--color-pink-600)] [--color-accent-content:var(--color-pink-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-pink-600)] dark:[--color-accent-content:var(--color-pink-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'rose' => '[--color-accent:var(--color-rose-500)] [--color-accent-content:var(--color-rose-500)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-rose-500)] dark:[--color-accent-content:var(--color-rose-400)] dark:[--color-accent-foreground:var(--color-white)]',
        default => '',
    } : '')
    ;

    // Exempt subtle and ghost buttons from receiving border roundness overrides from button.group...
    $attributes = $attributes->merge([
        'data-flux-group-target' => ! in_array($variant, ['subtle', 'ghost']),
    ]);
@endphp

<flux:with-tooltip :$attributes>
    <flux:button-or-link-pure :$type :attributes="$attributes->class($classes)" data-flux-button>
        <?php if ($loading): ?>
            <div class="absolute inset-0 flex items-center justify-center opacity-0" data-flux-loading-indicator>
                <flux:icon icon="loading" :variant="$iconVariant" :class="$iconClasses" />
            </div>
        <?php endif; ?>

        <?php if (is_string($iconLeading) && $iconLeading !== ''): ?>
            <flux:icon :icon="$iconLeading" :variant="$iconVariant" :class="$iconClasses" />
        <?php elseif ($iconLeading): ?>
            {{ $iconLeading }}
        <?php endif; ?>

        <?php if (($loading || $iconLeading || $iconTrailing) && ! $slot->isEmpty()): ?>
            <span>{{ $slot }}</span>
        <?php else: ?>
            {{ $slot }}
        <?php endif; ?>

        <?php if ($kbd): ?>
            <div class="text-xs text-zinc-400 dark:text-zinc-500">{{ $kbd }}</div>
        <?php endif; ?>

        <?php if (is_string($iconTrailing) && $iconTrailing !== ''): ?>
            <?php $iconClasses->add($square ? '' : '-ms-0.5'); ?>
            <flux:icon :icon="$iconTrailing" :variant="$iconTrailingVariant" :class="$iconTrailingClasses" />
        <?php elseif ($iconTrailing): ?>
            {{ $iconTrailing }}
        <?php endif; ?>
    </flux:button-or-link-pure>
</flux:with-tooltip>
