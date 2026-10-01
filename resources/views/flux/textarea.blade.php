@blaze(fold: true, unsafe: [
    // flux:with-field props
    'name', 'label', 'badge',
    'description', 'description:trailing',
    'label:badge', 'label:aside', 'label:trailing',
    'error:name', 'error:bag', 'error:message', 'error:icon', 'error:nested', 'error:deep',
])

@props([
    'name' => $attributes->whereStartsWith('wire:model')->first(),
    'resize' => 'vertical',
    'invalid' => null,
    'rows' => 3,
    'size' => null,
])

@php
$classes = Flux::classes()
    ->add('block w-full')
    ->add('shadow-xs disabled:shadow-none border')
    // ── Ukuran disamakan dengan input ──
    ->add(match ($size) {
        default => 'text-xs p-2 rounded-xl leading-[1.25rem]',
        'sm'    => 'text-[11px] p-1.5 rounded-lg leading-[1.125rem]',
        'xs'    => 'text-[10px] p-1.5 rounded-lg leading-[1rem]',
        'lg'    => 'text-sm p-2.5 rounded-xl leading-[1.375rem]',
        'xl'    => 'text-base p-3 rounded-2xl leading-[1.5rem]',
    })
    ->add('bg-white dark:bg-white/10 dark:disabled:bg-white/[7%]')
    ->add($resize ? match ($resize) {
        'none' => 'resize-none',
        'both' => 'resize',
        'horizontal' => 'resize-x',
        'vertical' => 'resize-y',
        default => 'resize-y',
    } : 'resize-none')
    ->add($rows === 'auto' ? 'field-sizing-content' : '')
    ->add('text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500')
    ->add('border-zinc-200 border-b-zinc-300/80 dark:border-white/10')
    ->add('focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 dark:focus:border-emerald-400 dark:focus:ring-emerald-400/40')
    ->add('data-invalid:shadow-none data-invalid:border-red-500 dark:data-invalid:border-red-500')
    ;
@endphp

<flux:with-field :$attributes>
    <textarea
        {{ $attributes->class($classes) }}
        rows="{{ $rows }}"
        @isset ($name) name="{{ $name }}" @endisset
        @unblaze(scope: ['name' => $name ?? null, 'invalid' => $invalid ?? false])
        <?php if ($scope['invalid'] || ($scope['name'] && $errors->has($scope['name']))): ?>
        aria-invalid="true" data-invalid
        <?php endif; ?>
        @endunblaze
        data-flux-control
        data-flux-textarea
    >{{ $slot }}</textarea>
</flux:with-field>
