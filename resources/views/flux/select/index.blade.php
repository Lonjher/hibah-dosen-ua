@blaze(fold: true)

@props([
    'variant' => 'default',
])

@php
$resolvedVariant = $variant !== 'default' && Flux::componentExists('select.variants.' . $variant)
    ? $variant
    : 'default';
@endphp

<flux:delegate-component :component="'select.variants.' . $resolvedVariant" {{ $attributes }}>
    {{ $slot }}
</flux:delegate-component>
