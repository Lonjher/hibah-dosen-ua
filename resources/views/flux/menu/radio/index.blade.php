@blaze(fold: true, unsafe: ['icon:trailing', 'icon:variant'])

@php $iconTrailing ??= $attributes->pluck('icon:trailing'); @endphp
@php $iconVariant ??= $attributes->pluck('icon:variant'); @endphp

@props([
    'iconVariant' => 'mini',
    'iconTrailing' => null,
    'variant' => 'default',
    'indent' => false,
    'suffix' => null,
    'label' => null,
    'kbd' => null,
])

@php
if ($kbd) $suffix = $kbd;

$iconClasses = Flux::classes()
    // When using the outline icon variant, we need to size it down to match the default icon sizes...
    ->add($iconVariant === 'outline' ? 'size-5' : null)
    ;

$iconTrailingClasses = Flux::classes()
    ->add('ms-auto')
    // When using the outline icon variant, we need to size it down to match the default icon sizes...
    ->add($iconVariant === 'outline' ? 'size-5' : null)
    ;

// checkbox — ganti group name jadi menu-radio untuk radio
$classes = Flux::classes()
    ->add('group/menu-radio flex items-center px-2 py-1 w-full focus:outline-hidden')
    ->add('rounded-lg')
    ->add('text-start text-[11px] font-medium leading-tight')
    ->add('transition-all duration-150')
    ->add('[[disabled]_&]:opacity-50 [&[disabled]]:opacity-50')
    ->add([
        'text-zinc-700 dark:text-zinc-200',
        'data-active:bg-white/50 dark:data-active:bg-white/10',
        'data-active:backdrop-blur-xl data-active:backdrop-saturate-200',
        'data-active:ring-1 data-active:ring-inset data-active:ring-white/60 dark:data-active:ring-white/15',
        'data-active:text-emerald-700 dark:data-active:text-emerald-300',
        '**:data-flux-menu-item-icon:text-zinc-400 dark:**:data-flux-menu-item-icon:text-zinc-500',
        '[&[data-active]_[data-flux-menu-item-icon]]:text-current',
    ]);
@endphp

<ui-menu-radio {{ $attributes->class($classes) }} data-flux-menu-item-has-icon data-flux-menu-radio>
    <div class="w-7">
        <div class="hidden group-data-checked/menu-radio:block">
            <flux:icon :variant="$iconVariant" icon="check" :class="$iconClasses" data-flux-menu-item-icon />
        </div>
    </div>

    {{ $slot->isNotEmpty() ? $slot : $label }}

    <?php if ($suffix): ?>
        <div class="ms-auto opacity-50 text-xs">
            {{ $suffix }}
        </div>
    <?php endif; ?>

    <?php if (is_string($iconTrailing) && $iconTrailing !== ''): ?>
        <flux:icon :icon="$iconTrailing" :variant="$iconVariant" :class="$iconTrailingClasses" data-flux-menu-item-icon />
    <?php elseif ($iconTrailing): ?>
        {{ $iconTrailing }}
    <?php endif; ?>
</ui-menu-radio>
