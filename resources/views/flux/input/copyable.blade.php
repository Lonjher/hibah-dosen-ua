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
    x-data="fluxInputCopyable"
    x-on:click="copy()"
    x-bind:data-copyable-copied="copied"
    aria-label="{{ __('Copy to clipboard') }}"
>
    <flux:icon.clipboard-document-check :variant="$iconVariant" class="size-3.5 hidden [[data-copyable-copied]>&]:block" />
    <flux:icon.clipboard-document :variant="$iconVariant" class="size-3.5 block [[data-copyable-copied]>&]:hidden" />
</flux:button>
