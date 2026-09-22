@blaze(fold: true, unsafe: ['icon:trailing', 'icon:variant'])

@php $iconTrailing ??= $attributes->pluck('icon:trailing'); @endphp
@php $iconVariant ??= $attributes->pluck('icon:variant'); @endphp

@props([
    'iconTrailing' => null,
    'iconVariant' => 'mini',
    'variant' => 'default',
    'suffix' => null,
    'value' => null,
    'icon' => null,
    'kbd' => null,
])

@php
if ($kbd) $suffix = $kbd;

$iconClasses = Flux::classes()
    ->add('me-2 shrink-0')
    ->add('text-zinc-400 dark:text-zinc-500')
    ->add('transition-colors duration-100')
    ->add('group-data-active/menu-item:text-current')
    ->add($iconVariant === 'outline' ? 'size-5' : 'size-4')
    ;

$trailingIconClasses = Flux::classes()
    ->add('ms-auto shrink-0 text-zinc-400 dark:text-zinc-500')
    ->add('transition-colors duration-100')
    ->add('group-data-active/menu-item:text-current')
    ->add($iconVariant === 'outline' ? 'size-5' : 'size-3.5')
    ;

$classes = Flux::classes()
    ->add('group/menu-item flex items-center w-full select-none')
    ->add('px-2.5 py-1.5 rounded-xl')
    ->add('text-start text-[12px] font-medium leading-tight')
    ->add('cursor-pointer focus:outline-hidden')
    ->add('transition-colors duration-100')
    ->add('[&[disabled]]:opacity-50 [&[disabled]]:pointer-events-none')
    ->add(match ($variant) {
        'danger' => [
            'text-zinc-700 dark:text-zinc-200',
            'data-active:bg-rose-50/80 data-active:text-rose-600',
            'dark:data-active:bg-rose-900/30 dark:data-active:text-rose-400',
            '**:data-flux-menu-item-icon:text-zinc-400 dark:**:data-flux-menu-item-icon:text-zinc-500',
            'data-active:**:data-flux-menu-item-icon:text-current',
        ],
        'default' => [
            'text-zinc-700 dark:text-zinc-200',
            'data-active:bg-emerald-50/70 data-active:text-emerald-700',
            'dark:data-active:bg-emerald-900/25 dark:data-active:text-emerald-300',
            '**:data-flux-menu-item-icon:text-zinc-400 dark:**:data-flux-menu-item-icon:text-zinc-500',
            'data-active:**:data-flux-menu-item-icon:text-current',
        ],
    })
    ;

$suffixClasses = Flux::classes()
    ->add('ms-auto shrink-0 text-[10px] font-semibold tracking-wide')
    ->add('text-zinc-400 dark:text-zinc-500')
    ->add('group-data-active/menu-item:text-current')
    ;
@endphp

<flux:button-or-link-pure
    :attributes="$attributes->class($classes)"
    data-flux-menu-item
    :data-flux-menu-item-has-icon="!! $icon"
>
    <?php if (is_string($icon) && $icon !== ''): ?>
        <flux:icon :$icon :variant="$iconVariant" :class="$iconClasses" data-flux-menu-item-icon />
    <?php elseif ($icon): ?>
        {{ $icon }}
    <?php else: ?>
        <div class="w-6 hidden [[data-flux-menu]:has(>[data-flux-menu-item-has-icon])_&]:block"></div>
    <?php endif; ?>

    <span class="truncate">{{ $slot }}</span>

    <?php if (is_string($suffix) && $suffix !== ''): ?>
        <div class="{{ $suffixClasses }}">{{ $suffix }}</div>
    <?php elseif ($suffix): ?>
        {{ $suffix }}
    <?php endif; ?>

    <?php if (is_string($iconTrailing) && $iconTrailing !== ''): ?>
        <flux:icon :icon="$iconTrailing" :variant="$iconVariant" :class="$trailingIconClasses" data-flux-menu-item-icon />
    <?php elseif ($iconTrailing): ?>
        {{ $iconTrailing }}
    <?php endif; ?>

    {{ $submenu ?? '' }}
</flux:button-or-link-pure>
