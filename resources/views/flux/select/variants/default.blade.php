@blaze

@props([
    'name' => $attributes->whereStartsWith('wire:model')->first(),
    'placeholder' => null,
    'invalid' => null,
    'size' => null,
])

@php
$invalid ??= ($name && $errors->has($name));

$classes = Flux::classes()
    ->add('appearance-none') // Strip the browser's default <select> styles...
    ->add('[:where(&)]:w-full ps-2 pe-7 block')
    ->add(match ($size) {
        default => 'h-7 py-1 text-xs leading-[1rem] rounded-full',
        'sm'    => 'h-6 py-0.5 text-[11px] leading-[0.875rem] rounded-full',
        'xs'    => 'h-5 py-0 text-[10px] leading-[0.75rem] rounded',
        'lg'    => 'h-8 py-1.5 text-sm leading-[1.125rem] rounded-full',
        'xl'    => 'h-10 py-2.5 text-base leading-[1.375rem] rounded-lg',
    })
    ->add('shadow-xs border')
    ->add('bg-white dark:bg-white/10 dark:disabled:bg-white/[7%]')
    ->add('text-zinc-700 dark:text-zinc-300 disabled:text-zinc-500 dark:disabled:text-zinc-400')
    // Make the placeholder match the text color of standard input placeholders...
    ->add('has-[option.placeholder:checked]:text-zinc-400 dark:has-[option.placeholder:checked]:text-zinc-400')
    // Options on Windows don't inherit dark mode styles, so we need to force them...
    ->add('dark:[&_option]:bg-zinc-700 dark:[&_option]:text-white')
    ->add('disabled:shadow-none')
    ->add($invalid
        ? 'border border-red-500'
        : 'border border-zinc-200 border-b-zinc-300/80 dark:border-white/10'
    )
    ;
@endphp

<select
    {{ $attributes->class($classes) }}
    @if ($invalid) aria-invalid="true" data-invalid @endif
    @isset ($name) name="{{ $name }}" @endisset
    @if (is_numeric($size)) size="{{ $size }}" @endif
    data-flux-control
    data-flux-select-native
    data-flux-group-target
>
    <?php if ($placeholder): ?>
        <option value="" disabled selected class="placeholder">{{ $placeholder }}</option>
    <?php endif; ?>

    {{ $slot }}
</select>
