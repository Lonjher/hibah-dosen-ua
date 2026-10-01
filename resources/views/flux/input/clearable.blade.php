@blaze(fold: true, memo: true)

@props([
    'iconVariant' => 'mini',
    'size' => null,
])

@php
$attributes = $attributes->merge([
    'variant' => 'subtle',
    'class' => '-me-0.5 [[data-flux-input]:has(input:placeholder-shown)_&]:hidden [[data-flux-input]:has(input[disabled])_&]:hidden',
    'square' => true,
    'size' => null,
]);
@endphp

<flux:button
    :$attributes
    :size="$size === 'lg' || $size === 'xl' ? 'sm' : 'xs'"
    x-data="fluxInputClearable"
    x-on:click="clear()"
    tabindex="-1"
    aria-label="{{ __('Clear input') }}"
    data-flux-clear-button
>
    <flux:icon.x-mark :variant="$iconVariant" class="size-3.5" />
</flux:button>
