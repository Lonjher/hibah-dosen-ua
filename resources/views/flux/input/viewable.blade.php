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
    x-data="fluxInputViewable"
    x-on:click="toggle()"
    x-bind:data-viewable-open="open"
    aria-label="{{ __('Toggle password visibility') }}"
>
    <flux:icon.eye-slash :variant="$iconVariant" class="size-3.5 hidden [[data-viewable-open]>&]:block" />
    <flux:icon.eye :variant="$iconVariant" class="size-3.5 block [[data-viewable-open]>&]:hidden" />
</flux:button>
