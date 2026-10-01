@blaze(fold: true, memo: true)

@props([
    'iconVariant' => 'mini',
    'size' => null,
])

@php
$attributes = $attributes->merge([
    'variant' => 'subtle',
    'class' => '-me-0.5',
    'square' => true,
    'size' => null,
]);
@endphp

<flux:button
    :$attributes
    :size="$size === 'lg' || $size === 'xl' ? 'sm' : 'xs'"
>
    <flux:icon.chevron-down :variant="$iconVariant" class="size-3.5" />
</flux:button>
