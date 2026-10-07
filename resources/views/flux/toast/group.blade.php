@blaze(fold: true, safe: ['position'])

@props([
    'position' => 'top right',
    'expanded' => false,
])

<ui-toast-group
    x-data
    x-on:toast-show.document="$el.showToast($event.detail)"
    popover="manual"
    position="{{ $position }}"
    @if ($expanded) expanded @endif
    wire:ignore
>
    {{ $slot }}
</ui-toast-group>
