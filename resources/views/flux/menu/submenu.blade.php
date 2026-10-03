@blaze(fold: true, unsafe: ['icon:trailing', 'icon:variant'])

@php $iconTrailing ??= $attributes->pluck('icon:trailing'); @endphp
@php $iconVariant ??= $attributes->pluck('icon:variant'); @endphp

@props([
    'iconVariant' => 'mini',
    'iconTrailing' => null,
    'heading' => '',
    'icon' => null,
    'keepOpen' => false,
])

@php
$iconClasses = Flux::classes()
    ->add('ms-auto text-zinc-400 [[data-flux-menu-item]:hover_&]:text-current')
    ->add($iconVariant === 'outline' ? 'size-4' : 'size-3');
@endphp

<ui-submenu data-flux-menu-submenu>
    <flux:menu.item :$icon :$iconVariant>
        {{ $heading }}

        <x-slot:suffix>
            <?php if (is_string($iconTrailing) && $iconTrailing !== ''): ?>
                <flux:icon :icon="$iconTrailing" :variant="$iconVariant" :class="$iconClasses" />
            <?php elseif ($iconTrailing): ?>
                {{ $iconTrailing }}
            <?php else: ?>
                <flux:icon icon="chevron-right" :variant="$iconVariant" :class="$iconClasses->add('rtl:hidden')" />
                <flux:icon icon="chevron-left" :variant="$iconVariant" :class="$iconClasses->add('hidden rtl:inline')" />
            <?php endif; ?>
        </x-slot:suffix>
    </flux:menu.item>

    <flux:menu :keep-open="$keepOpen" class="[:where(&)]:min-w-[8rem] [:where(&)]:max-w-[min(16rem,calc(100vw-1rem))]">
        {{ $slot }}
    </flux:menu>
</ui-submenu>
